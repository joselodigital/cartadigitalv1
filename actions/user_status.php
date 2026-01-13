<?php
// actions/user_status.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $new_status = $_GET['status'] === 'blocked' ? 'blocked' : 'active';
    
    // Prevent blocking yourself
    if ($id == $_SESSION['user_id']) {
        redirect('../index.php?view=users_list&error=No puedes bloquear tu propia cuenta');
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        
        $action = $new_status === 'blocked' ? 'block_user' : 'unblock_user';
        logAction($_SESSION['user_id'], $action, "Usuario ID: $id estado cambiado a $new_status");

        redirect('../index.php?view=users_list&success=Estado de usuario actualizado');
    } catch (PDOException $e) {
        redirect('../index.php?view=users_list&error=' . urlencode($e->getMessage()));
    }
} else {
    redirect('../index.php?view=users_list');
}
?>
