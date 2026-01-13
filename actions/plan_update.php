<?php
// actions/plan_update.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $name = clean($_POST['name']);
    $price = floatval($_POST['price']);
    $product_limit = intval($_POST['product_limit']);
    
    // Checkboxes are not sent if unchecked
    $branding_hidden = isset($_POST['branding_hidden']) ? 1 : 0;
    $allow_private_catalog = isset($_POST['allow_private_catalog']) ? 1 : 0;
    $custom_domain = isset($_POST['custom_domain']) ? 1 : 0;
    $ads_enabled = isset($_POST['ads_enabled']) ? 1 : 0;
    $is_hidden = isset($_POST['is_hidden']) ? 1 : 0;

    if (empty($name) || $price < 0 || $product_limit < 1) {
        redirect('../index.php?view=plans_list&error=Datos inválidos');
    }

    try {
        $stmt = $pdo->prepare("UPDATE plans SET name = ?, price = ?, product_limit = ?, branding_hidden = ?, allow_private_catalog = ?, custom_domain = ?, ads_enabled = ?, is_hidden = ? WHERE id = ?");
        $stmt->execute([$name, $price, $product_limit, $branding_hidden, $allow_private_catalog, $custom_domain, $ads_enabled, $is_hidden, $id]);
        
        logAction($_SESSION['user_id'], 'update_plan', "Actualizado Plan ID: $id ($name).");

        redirect('../index.php?view=plans_list&success=Plan actualizado correctamente');
    } catch (PDOException $e) {
        redirect('../index.php?view=plans_list&error=' . urlencode($e->getMessage()));
    }
}
?>
