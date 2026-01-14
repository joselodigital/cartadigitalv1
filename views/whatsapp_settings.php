<?php
requireRole('super_admin');

try {
    $stmt = $pdo->query("SELECT * FROM whatsapp_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS whatsapp_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                base_message_es TEXT NOT NULL,
                base_message_en TEXT NOT NULL,
                default_language ENUM('es','en') NOT NULL DEFAULT 'es',
                include_emojis TINYINT(1) NOT NULL DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $stmtIns = $pdo->prepare("
            INSERT INTO whatsapp_settings (id, base_message_es, base_message_en, default_language, include_emojis)
            VALUES (1, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE id = id
        ");
        $stmtIns->execute([
            'Hola, te escribo desde el catálogo de {negocio}. Me interesa {producto} (Precio: ${precio}).',
            'Hello, I am messaging from the catalog of {business}. I am interested in {product} (Price: ${price}).',
            'es',
            1
        ]);
        $stmt = $pdo->query("SELECT * FROM whatsapp_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $settings = false;
    }
}

if (!$settings) {
    $settings = [
        'base_message_es' => 'Hola, te escribo desde el catálogo de {negocio}. Me interesa {producto} (Precio: ${precio}).',
        'base_message_en' => 'Hello, I am messaging from the catalog of {business}. I am interested in {product} (Price: ${price}).',
        'default_language' => 'es',
        'include_emojis' => 1
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>WhatsApp Inteligente</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_super.php'; ?>

    <div class="content">
        <?php include 'views/partials/topbar_super.php'; ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>WhatsApp Inteligente</h1>
        </div>

        <div class="card">
            <p class="hint">
                Plantillas con variables: 
                <code>{negocio}</code> / <code>{business}</code>, 
                <code>{producto}</code> / <code>{product}</code>, 
                <code>{precio}</code> / <code>{price}</code>.
            </p>
            <form action="actions/whatsapp_settings_update.php" method="POST">
                <label>Mensaje Base (ES):</label>
                <textarea name="base_message_es" rows="3" required><?php echo htmlspecialchars($settings['base_message_es']); ?></textarea>
                
                <label>Mensaje Base (EN):</label>
                <textarea name="base_message_en" rows="3" required><?php echo htmlspecialchars($settings['base_message_en']); ?></textarea>
                
                <label>Idioma por defecto:</label>
                <select name="default_language" required>
                    <option value="es" <?php echo $settings['default_language']==='es'?'selected':''; ?>>ES</option>
                    <option value="en" <?php echo $settings['default_language']==='en'?'selected':''; ?>>EN</option>
                </select>
                
                <label>Emojis:</label>
                <select name="include_emojis" required>
                    <option value="1" <?php echo (int)$settings['include_emojis']===1?'selected':''; ?>>Activados 😎</option>
                    <option value="0" <?php echo (int)$settings['include_emojis']===0?'selected':''; ?>>Desactivados</option>
                </select>
                
                <button type="submit" class="btn btn-blue">Guardar</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();
    </script>
</body>
</html>
