<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../index.php?view=login');
if (!(hasRole('admin_negocio') || hasRole('colaborador'))) redirect('../index.php?view=login');

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            business_id INT NOT NULL,
            user_id INT NOT NULL,
            subject VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('open','closed') NOT NULL DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_SESSION['business_id'];
    $user_id = $_SESSION['user_id'];
    $subject = clean($_POST['subject']);
    $message = clean($_POST['message']);
    if (empty($subject) || empty($message)) {
        $redir = hasRole('admin_negocio') ? '../index.php?view=dashboard_business' : '../index.php?view=dashboard_collab';
        redirect($redir . '&error=' . urlencode('Asunto y mensaje requeridos'));
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO tickets (business_id, user_id, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$business_id, $user_id, $subject, $message]);
        logAction($user_id, 'create_ticket', "Asunto: $subject");
        $redir = hasRole('admin_negocio') ? '../index.php?view=dashboard_business' : '../index.php?view=dashboard_collab';
        redirect($redir . '&success=' . urlencode('Ticket enviado'));
    } catch (PDOException $e) {
        $redir = hasRole('admin_negocio') ? '../index.php?view=dashboard_business' : '../index.php?view=dashboard_collab';
        redirect($redir . '&error=' . urlencode($e->getMessage()));
    }
} else {
    redirect('../index.php');
}
