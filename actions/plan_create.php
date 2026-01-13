<?php
// actions/plan_create.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $price = floatval($_POST['price']);
    $product_limit = intval($_POST['product_limit']);
    
    // Checkboxes
    $branding_hidden = isset($_POST['branding_hidden']) ? 1 : 0;
    $allow_private_catalog = isset($_POST['allow_private_catalog']) ? 1 : 0;
    $custom_domain = isset($_POST['custom_domain']) ? 1 : 0;
    $ads_enabled = isset($_POST['ads_enabled']) ? 1 : 0;
    $is_hidden = isset($_POST['is_hidden']) ? 1 : 0;

    if (empty($name) || $price < 0 || $product_limit < 1) {
        redirect('../index.php?view=plans_list&error=Datos inválidos');
    }

    // Generate Slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    // Ensure slug is unique
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM plans WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        $slug .= '-' . time();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO plans (name, slug, price, product_limit, branding_hidden, allow_private_catalog, custom_domain, ads_enabled, is_hidden) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $price, $product_limit, $branding_hidden, $allow_private_catalog, $custom_domain, $ads_enabled, $is_hidden]);
        
        $id = $pdo->lastInsertId();
        logAction($_SESSION['user_id'], 'create_plan', "Creado Plan ID: $id ($name)");

        redirect('../index.php?view=plans_list&success=Plan creado correctamente');
    } catch (PDOException $e) {
        redirect('../index.php?view=plans_list&error=' . urlencode($e->getMessage()));
    }
}
?>