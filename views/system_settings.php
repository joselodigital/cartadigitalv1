<?php
requireRole('super_admin');

try {
    $stmt = $pdo->query("SELECT * FROM app_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS app_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                saas_name VARCHAR(150) NOT NULL,
                main_domain VARCHAR(255) NOT NULL,
                system_logo VARCHAR(255),
                footer_text VARCHAR(255) NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $stmtIns = $pdo->prepare("
            INSERT INTO app_settings (id, saas_name, main_domain, system_logo, footer_text)
            VALUES (1, ?, ?, NULL, ?)
            ON DUPLICATE KEY UPDATE id = id
        ");
        $stmtIns->execute(['Catálogo Digital', 'catalogodigital.local', 'Powered by Joselo Digital']);
        $stmt = $pdo->query("SELECT * FROM app_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $settings = false;
    }
}

if (!$settings) {
    $settings = [
        'saas_name' => 'Catálogo Digital',
        'main_domain' => 'catalogodigital.local',
        'system_logo' => '',
        'footer_text' => 'Powered by Joselo Digital'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ajustes del Sistema</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .preview { display:flex; align-items:center; gap:12px; margin:10px 0; }
        .preview img { max-height:50px; border-radius:4px; }
    </style>
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_super.php'; ?>

    <div class="content">
        <?php include 'views/partials/topbar_super.php'; ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Ajustes del Sistema</h1>
        </div>

        <div class="card">
            <form action="actions/system_settings_update.php" method="POST" enctype="multipart/form-data">
                <label>Logo del Sistema:</label>
                <div class="preview">
                    <?php if(!empty($settings['system_logo'])): ?>
                        <img src="public/uploads/<?php echo htmlspecialchars($settings['system_logo']); ?>" alt="Logo">
                    <?php else: ?>
                        <span style="color:#888;">Sin logo</span>
                    <?php endif; ?>
                </div>
                <input type="file" name="system_logo" accept="image/*">

                <label>Nombre del SaaS:</label>
                <input type="text" name="saas_name" value="<?php echo htmlspecialchars($settings['saas_name']); ?>" required>

                <label>Dominio principal:</label>
                <input type="text" name="main_domain" value="<?php echo htmlspecialchars($settings['main_domain']); ?>" required>

                <label>Footer del catálogo:</label>
                <input type="text" name="footer_text" value="<?php echo htmlspecialchars($settings['footer_text']); ?>" required>

                <button type="submit" class="btn btn-blue">Guardar</button>
            </form>
        </div>
    </div>
</body>
</html>

