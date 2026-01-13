<?php
// scripts/update_db_features.php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("ALTER TABLE businesses 
        ADD COLUMN feature1_title VARCHAR(100) DEFAULT 'Fácil de Comprar',
        ADD COLUMN feature1_desc VARCHAR(255) DEFAULT 'Elige tus productos favoritos y pídelos directamente por WhatsApp.',
        ADD COLUMN feature1_icon VARCHAR(50) DEFAULT 'shopping-bag',
        ADD COLUMN feature2_title VARCHAR(100) DEFAULT 'Compra Segura',
        ADD COLUMN feature2_desc VARCHAR(255) DEFAULT 'Tratas directamente con nosotros, sin intermediarios ni comisiones.',
        ADD COLUMN feature2_icon VARCHAR(50) DEFAULT 'shield',
        ADD COLUMN feature3_title VARCHAR(100) DEFAULT 'Atención Personal',
        ADD COLUMN feature3_desc VARCHAR(255) DEFAULT 'Te atendemos personalmente para confirmar detalles de tu pedido.',
        ADD COLUMN feature3_icon VARCHAR(50) DEFAULT 'message-circle'
    ");
    echo "Columns added successfully";
} catch (PDOException $e) {
    echo "Error (columns might already exist): " . $e->getMessage();
}
?>