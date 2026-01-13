<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Add business_type column
    $pdo->exec("ALTER TABLE businesses ADD COLUMN business_type VARCHAR(20) DEFAULT 'product'");
    echo "Columna 'business_type' añadida correctamente.\n";
} catch (PDOException $e) {
    echo "Error (o columna ya existe): " . $e->getMessage() . "\n";
}
?>