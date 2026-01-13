<?php
// actions/business_delete.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM businesses WHERE id = ?");
        $stmt->execute([$id]);
        logAction($_SESSION['user_id'], 'delete_business', "Eliminado negocio ID: $id");
        redirect('../index.php?view=dashboard_super&success=Negocio eliminado');
    } catch (PDOException $e) {
        redirect('../index.php?view=dashboard_super&error=' . urlencode($e->getMessage()));
    }
} else {
    redirect('../index.php?view=dashboard_super');
}
?>
