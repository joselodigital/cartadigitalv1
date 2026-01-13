<?php
// actions/reduce_stock.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['business_id']) || !isset($input['items']) || !is_array($input['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$business_id = (int)$input['business_id'];
$items = $input['items'];

if (empty($items)) {
    echo json_encode(['success' => true, 'message' => 'Carrito vacío']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($items as $item) {
        $product_id = (int)$item['id'];
        $qty = (int)$item['qty'];

        if ($qty <= 0) continue;

        // Lock the row for update and check stock
        $stmt = $pdo->prepare("SELECT name, stock FROM products WHERE id = ? AND business_id = ? FOR UPDATE");
        $stmt->execute([$product_id, $business_id]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception("Producto ID $product_id no encontrado o no pertenece al negocio");
        }

        if ($product['stock'] < $qty) {
            throw new Exception("Stock insuficiente para: " . $product['name'] . " (Disponible: " . $product['stock'] . ")");
        }

        // Deduct stock
        $updateStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $updateStmt->execute([$qty, $product_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
