<?php
// actions/payment_method_delete.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('admin_negocio');

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $business_id = $_SESSION['business_id'];

    try {
        // Verify ownership and get image path
        $stmt = $pdo->prepare("SELECT qr_image FROM business_payment_methods WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, $business_id]);
        $method = $stmt->fetch();

        if ($method) {
            // Delete image file if exists
            if ($method['qr_image'] && file_exists('../public/uploads/' . $method['qr_image'])) {
                unlink('../public/uploads/' . $method['qr_image']);
            }

            // Delete record
            $stmt = $pdo->prepare("DELETE FROM business_payment_methods WHERE id = ?");
            $stmt->execute([$id]);

            logAction($_SESSION['user_id'], 'delete_payment_method', "Eliminado método de pago ID: $id");
            redirect('../index.php?view=dashboard_business&success=' . urlencode('Método eliminado'));
        } else {
            redirect('../index.php?view=dashboard_business&error=' . urlencode('Método no encontrado'));
        }
    } catch (PDOException $e) {
        redirect('../index.php?view=dashboard_business&error=' . urlencode($e->getMessage()));
    }
}
?>
