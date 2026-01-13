<?php
// actions/user_delete.php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Allow both super_admin and admin_negocio (for collabs)
if (!isLoggedIn()) redirect('../index.php?view=login');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    if (hasRole('super_admin')) {
        // Super admin can delete anyone (except maybe self, but simple logic for now)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        logAction($_SESSION['user_id'], 'delete_user', "Eliminado usuario ID: $id");

        redirect('../index.php?view=dashboard_super');
    } elseif (hasRole('admin_negocio')) {
        // Business admin can only delete their collaborators
        $business_id = $_SESSION['business_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND business_id = ? AND role_id = 3");
        $stmt->execute([$id, $business_id]);
        redirect('../index.php?view=dashboard_business');
    }
}
?>