<?php
// actions/product_delete.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole(['admin_negocio', 'colaborador']);

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $business_id = $_SESSION['business_id'];

    // Determine redirect URL based on role
    $redirect_url = ($_SESSION['role'] === 'colaborador') 
        ? '../index.php?view=dashboard_collab' 
        : '../index.php?view=dashboard_business';

    try {
        // Only delete if belongs to business
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $business_id]);
        logAction($_SESSION['user_id'], 'delete_product', "Eliminado producto ID: $id del negocio ID: $business_id");
        redirect($redirect_url . '&success=Producto eliminado');
    } catch (PDOException $e) {
        redirect($redirect_url . '&error=' . urlencode($e->getMessage()));
    }
}
?>
