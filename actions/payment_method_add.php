<?php
// actions/payment_method_add.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('admin_negocio');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_SESSION['business_id'];
    $method_name = clean($_POST['method_name']);
    $account_details = clean($_POST['account_details']);
    
    $qr_image = null;

    // Handle Image Upload
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === 0) {
        $upload_dir = '../public/uploads/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $filename = 'qr_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $upload_dir . $filename)) {
                $qr_image = $filename;
            }
        }
    }

    if (empty($method_name)) {
        redirect('../index.php?view=dashboard_business&error=' . urlencode('El nombre del método es obligatorio'));
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO business_payment_methods (business_id, method_name, account_details, qr_image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$business_id, $method_name, $account_details, $qr_image]);
        
        logAction($_SESSION['user_id'], 'add_payment_method', "Añadido método de pago: $method_name");

        redirect('../index.php?view=dashboard_business&success=' . urlencode('Método de pago agregado correctamente'));
    } catch (PDOException $e) {
        redirect('../index.php?view=dashboard_business&error=' . urlencode($e->getMessage()));
    }
}
?>
