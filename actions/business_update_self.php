<?php
// actions/business_update_self.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('admin_negocio');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_SESSION['business_id'];
    $name = clean($_POST['name']);
    $whatsapp = clean($_POST['whatsapp']);
    $payment_info = clean($_POST['payment_info']);
    $facebook = clean($_POST['facebook'] ?? '');
    $instagram = clean($_POST['instagram'] ?? '');
    $tiktok = clean($_POST['tiktok'] ?? '');
    $twitter = clean($_POST['twitter'] ?? '');
    $website = clean($_POST['website'] ?? '');
    $business_type = clean($_POST['business_type'] ?? 'product');
    $currency = clean($_POST['currency'] ?? 'PEN');

    $slogan = clean($_POST['slogan'] ?? '');
    $color_primary = clean($_POST['color_primary'] ?? '#2563eb');
    $bg_color = clean($_POST['bg_color'] ?? '#f8fafc');

    $about_title = clean($_POST['about_title'] ?? 'Tu mejor opción en calidad y servicio');
    $description = clean($_POST['description'] ?? '');

    $feature1_title = clean($_POST['feature1_title'] ?? 'Fácil de Comprar');
    $feature1_desc = clean($_POST['feature1_desc'] ?? 'Elige tus productos favoritos y pídelos directamente por WhatsApp.');
    $feature2_title = clean($_POST['feature2_title'] ?? 'Compra Segura');
    $feature2_desc = clean($_POST['feature2_desc'] ?? 'Tratas directamente con nosotros, sin intermediarios ni comisiones.');
    $feature3_title = clean($_POST['feature3_title'] ?? 'Atención Personal');
    $feature3_desc = clean($_POST['feature3_desc'] ?? 'Te atendemos personalmente para confirmar detalles de tu pedido.');

    // Image Upload Handling
    $stmt = $pdo->prepare("SELECT payment_image, cover_image FROM businesses WHERE id = ?");
    $stmt->execute([$business_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $payment_image = $row['payment_image'];
    $cover_image = $row['cover_image'];

    if (isset($_FILES['payment_image']) && $_FILES['payment_image']['error'] === 0) {
        $upload_dir = '../public/uploads/';
        $ext = pathinfo($_FILES['payment_image']['name'], PATHINFO_EXTENSION);
        $filename = 'pay_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['payment_image']['tmp_name'], $upload_dir . $filename)) {
            $payment_image = $filename;
        }
    }

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $upload_dir = '../public/uploads/';
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $filename = 'cover_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $filename)) {
            $cover_image = $filename;
        }
    }

    // Check Plan for Privacy capability
    $stmt = $pdo->prepare("SELECT plan FROM businesses WHERE id = ?");
    $stmt->execute([$business_id]);
    $current_plan = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT allow_private_catalog FROM plans WHERE slug = ?");
    $stmt->execute([$current_plan]);
    $allow_private = $stmt->fetchColumn();

    $is_private = 0;
    $password = null;

    if ($allow_private) {
        $is_private = isset($_POST['is_private']) ? 1 : 0;
        $password = isset($_POST['password']) ? clean($_POST['password']) : null;
    }

    try {
        $stmt = $pdo->prepare("UPDATE businesses SET name = ?, whatsapp = ?, payment_info = ?, payment_image = ?, cover_image = ?, facebook = ?, instagram = ?, tiktok = ?, twitter = ?, website = ?, is_private = ?, password = ?, feature1_title = ?, feature1_desc = ?, feature2_title = ?, feature2_desc = ?, feature3_title = ?, feature3_desc = ?, business_type = ?, currency = ?, slogan = ?, color_primary = ?, bg_color = ?, about_title = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $whatsapp, $payment_info, $payment_image, $cover_image, $facebook, $instagram, $tiktok, $twitter, $website, $is_private, $password, $feature1_title, $feature1_desc, $feature2_title, $feature2_desc, $feature3_title, $feature3_desc, $business_type, $currency, $slogan, $color_primary, $bg_color, $about_title, $description, $business_id]);
        
        logAction($_SESSION['user_id'], 'update_business_config', "Actualizado negocio ID: $business_id. Privado: $is_private");

        redirect('../index.php?view=dashboard_business&success=Configuración actualizada');
    } catch (PDOException $e) {
        redirect('../index.php?view=dashboard_business&error=' . urlencode($e->getMessage()));
    }
}
?>