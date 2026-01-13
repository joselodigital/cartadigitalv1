<?php
// actions/business_create.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $whatsapp = clean($_POST['whatsapp']);
    $plan = clean($_POST['plan']);
    $currency = clean($_POST['currency']);
    $subscription_end = !empty($_POST['subscription_end']) ? $_POST['subscription_end'] : NULL;
    
    // Generate slug from name
    $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $slug = $baseSlug;
    $counter = 1;

    // Ensure uniqueness
    while (true) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM businesses WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetchColumn() == 0) {
            break;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    if (empty($name)) {
        redirect('../index.php?view=dashboard_super&error=Nombre requerido');
    }

    $logo_name = '';
    // Handle Logo Upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $upload_dir = '../public/uploads/';
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logo_name = 'logo_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_name);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO businesses (name, slug, whatsapp, logo, plan, currency, subscription_end) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $whatsapp, $logo_name, $plan, $currency, $subscription_end]);
        
        $new_id = $pdo->lastInsertId();
        logAction($_SESSION['user_id'], 'create_business', "Creado negocio ID: $new_id ($name)");

        redirect('../index.php?view=dashboard_super&success=Negocio creado');
    } catch (PDOException $e) {
        redirect('../index.php?view=dashboard_super&error=' . urlencode($e->getMessage()));
    }
}
?>