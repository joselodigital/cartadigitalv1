<?php
// actions/business_update.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = clean($_POST['name']);
    $status = clean($_POST['status']);
    $plan = clean($_POST['plan']);
    $currency = clean($_POST['currency']);
    $subscription_end = !empty($_POST['subscription_end']) ? $_POST['subscription_end'] : NULL;
    $internal_notes = isset($_POST['internal_notes']) ? trim($_POST['internal_notes']) : '';
    
    // Fetch current logo to handle replacement
    $stmt = $pdo->prepare("SELECT logo FROM businesses WHERE id = ?");
    $stmt->execute([$id]);
    $business = $stmt->fetch();
    $logo_name = $business['logo'];

    // Handle Logo Upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $upload_dir = '../public/uploads/';
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $new_logo_name = 'logo_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $new_logo_name)) {
             // Delete old logo if exists
             if ($logo_name && file_exists($upload_dir . $logo_name)) {
                 unlink($upload_dir . $logo_name);
             }
             $logo_name = $new_logo_name;
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE businesses SET name = ?, status = ?, logo = ?, plan = ?, currency = ?, subscription_end = ?, internal_notes = ? WHERE id = ?");
        $stmt->execute([$name, $status, $logo_name, $plan, $currency, $subscription_end, $internal_notes, $id]);
        redirect('../index.php?view=dashboard_super&success=Negocio actualizado');
    } catch (PDOException $e) {
        redirect('../index.php?view=dashboard_super&error=' . urlencode($e->getMessage()));
    }
}
?>