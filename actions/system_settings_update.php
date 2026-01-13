<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saas_name = isset($_POST['saas_name']) ? trim($_POST['saas_name']) : '';
    $main_domain = isset($_POST['main_domain']) ? trim($_POST['main_domain']) : '';
    $footer_text = isset($_POST['footer_text']) ? trim($_POST['footer_text']) : '';

    if ($saas_name === '' || $main_domain === '' || $footer_text === '') {
        redirect('../index.php?view=system_settings&error=' . urlencode('Todos los campos son obligatorios'));
    }

    $logo_name = null;
    if (isset($_FILES['system_logo']) && $_FILES['system_logo']['error'] === 0) {
        $max_size = 2 * 1024 * 1024;
        if ($_FILES['system_logo']['size'] > $max_size) {
            redirect('../index.php?view=system_settings&error=' . urlencode('Logo demasiado pesado (Máx 2MB)'));
        }
        $upload_dir = '../public/uploads/';
        $ext = pathinfo($_FILES['system_logo']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array(strtolower($ext), $allowed)) {
            redirect('../index.php?view=system_settings&error=' . urlencode('Formato de imagen no permitido'));
        }
        $logo_name = 'system_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['system_logo']['tmp_name'], $upload_dir . $logo_name);
    }

    try {
        $stmt = $pdo->query("SELECT system_logo FROM app_settings WHERE id = 1");
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_logo = $current ? $current['system_logo'] : null;

        if ($logo_name && $current_logo) {
            $old_path = '../public/uploads/' . $current_logo;
            if (file_exists($old_path)) unlink($old_path);
        }

        $stmt = $pdo->prepare("
            INSERT INTO app_settings (id, saas_name, main_domain, system_logo, footer_text)
            VALUES (1, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                saas_name = VALUES(saas_name),
                main_domain = VALUES(main_domain),
                system_logo = VALUES(system_logo),
                footer_text = VALUES(footer_text)
        ");
        $stmt->execute([$saas_name, $main_domain, $logo_name ? $logo_name : $current_logo, $footer_text]);
        redirect('../index.php?view=system_settings&success=' . urlencode('Ajustes guardados'));
    } catch (PDOException $e) {
        redirect('../index.php?view=system_settings&error=' . urlencode($e->getMessage()));
    }
}

redirect('../index.php?view=system_settings');
?>

