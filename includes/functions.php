<?php
// includes/functions.php

session_start();

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('index.php?view=login');
    }
}

function hasRole($roles) {
    if (is_array($roles)) {
        return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === $roles;
}

function requireRole($roles) {
    requireLogin();
    
    // Check if business is suspended (for business admins and collaborators)
    if (isset($_SESSION['business_id']) && $_SESSION['business_id']) {
        global $pdo;
        if (!isset($pdo)) {
            require_once __DIR__ . '/../config/db.php';
        }
        
        $stmt = $pdo->prepare("SELECT status FROM businesses WHERE id = ?");
        $stmt->execute([$_SESSION['business_id']]);
        $status = $stmt->fetchColumn();
        
        if ($status === 'suspended') {
            die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
                    <h1 style='color:orange'>Cuenta Suspendida</h1>
                    <p>Este negocio ha sido suspendido temporalmente.</p>
                    <p>Por favor, contacta al soporte.</p>
                    <a href='actions/logout.php'>Cerrar Sesión</a>
                 </div>");
        }
    }

    if (!hasRole($roles)) {
        $role_str = is_array($roles) ? implode(' o ', $roles) : $roles;
        die("Acceso denegado. Se requiere rol: $role_str");
    }
}

// Helper to sanitize input
function clean($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function logAction($user_id, $action, $details = null) {
    global $pdo;
    if (!isset($pdo)) {
        require_once __DIR__ . '/../config/db.php';
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT NULL,
                ip_address VARCHAR(64) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_created (user_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (PDOException $e) {
        // Silently fail logging to avoid breaking the app flow
        error_log("Audit Log Error: " . $e->getMessage());
    }
}
?>
