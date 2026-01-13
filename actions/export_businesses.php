<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=negocios_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers
fputcsv($output, ['ID', 'Nombre', 'Slug', 'WhatsApp', 'Plan', 'Estado', 'Vence', 'Fecha Creación', 'Notas Internas']);

$stmt = $pdo->query("SELECT id, name, slug, whatsapp, plan, status, subscription_end, created_at, internal_notes FROM businesses ORDER BY created_at DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
