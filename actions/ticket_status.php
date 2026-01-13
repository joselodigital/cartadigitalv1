<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $new_status = $_GET['status'] === 'closed' ? 'closed' : 'open';
    try {
        $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        logAction($_SESSION['user_id'], 'update_ticket', "Ticket ID: $id estado $new_status");
        redirect('../index.php?view=tickets&status=' . $new_status);
    } catch (PDOException $e) {
        redirect('../index.php?view=tickets&error=' . urlencode($e->getMessage()));
    }
} else {
    redirect('../index.php?view=tickets');
}
