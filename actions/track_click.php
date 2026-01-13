<?php
// actions/track_click.php
require_once '../config/db.php';

// Allow CORS if needed, or just rely on same-origin
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$business_id = isset($data['business_id']) ? intval($data['business_id']) : 0;

if ($business_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Business ID']);
    exit;
}

try {
    // Insert click record
    $stmt = $pdo->prepare("INSERT INTO whatsapp_clicks (business_id, clicked_at) VALUES (?, NOW())");
    $stmt->execute([$business_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Log error if needed but keep response clean
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>