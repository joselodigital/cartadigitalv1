<?php
// actions/auth_login.php
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        redirect('../index.php?view=login&error=Campos requeridos');
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                email VARCHAR(100) NOT NULL,
                ip_address VARCHAR(64) NULL,
                attempts INT NOT NULL DEFAULT 0,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (PDOException $e) {}

    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Check if user is blocked
        if ($user['status'] === 'blocked') {
            redirect('../index.php?view=login&error=Tu cuenta ha sido bloqueada. Contacta al administrador.');
        }

        // Login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['business_id'] = $user['business_id'];

        // Update Last Login and IP
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?");
        $stmt->execute([$ip, $user['id']]);

        try {
            $stmtLA = $pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
            $stmtLA->execute([$email]);
        } catch (PDOException $e) {}

        // Log Action
        logAction($user['id'], 'login', 'Inicio de sesión exitoso');

        // Redirect based on role
        switch ($user['role_name']) {
            case 'super_admin':
                redirect('../index.php?view=dashboard_super');
                break;
            case 'admin_negocio':
                redirect('../index.php?view=dashboard_business');
                break;
            case 'colaborador':
                redirect('../index.php?view=dashboard_collab');
                break;
            default:
                redirect('../index.php?view=login&error=Rol desconocido');
        }
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        try {
            $stmtLA = $pdo->prepare("SELECT id, attempts FROM login_attempts WHERE email = ?");
            $stmtLA->execute([$email]);
            $la = $stmtLA->fetch();
            if ($la) {
                $attempts = (int)$la['attempts'] + 1;
                $stmtUpd = $pdo->prepare("UPDATE login_attempts SET attempts = ?, ip_address = ?, last_attempt = NOW() WHERE id = ?");
                $stmtUpd->execute([$attempts, $ip, $la['id']]);
            } else {
                $attempts = 1;
                $user_id_val = $user ? $user['id'] : null;
                $stmtIns = $pdo->prepare("INSERT INTO login_attempts (user_id, email, ip_address, attempts) VALUES (?, ?, ?, ?)");
                $stmtIns->execute([$user_id_val, $email, $ip, $attempts]);
            }
        } catch (PDOException $e) {
            $attempts = 1;
        }

        if ($user && isset($attempts) && $attempts >= 5) {
            try {
                $stmtBlock = $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
                $stmtBlock->execute([$user['id']]);
            } catch (PDOException $e) {}
            logAction($user['id'], 'block_user', "Bloqueo automático por 5 intentos fallidos de login");
            redirect('../index.php?view=login&error=Cuenta bloqueada por múltiples intentos fallidos');
        }

        logAction($user ? $user['id'] : null, 'login_failed', "Email: $email. Intentos: " . (isset($attempts) ? $attempts : 1));
        $msg = isset($attempts) ? "Credenciales incorrectas (Intento $attempts de 5)" : "Credenciales incorrectas";
        redirect('../index.php?view=login&error=' . urlencode($msg));
    }
} else {
    redirect('../index.php?view=login');
}
?>
