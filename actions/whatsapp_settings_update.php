<?php
// actions/whatsapp_settings_update.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $base_es = isset($_POST['base_message_es']) ? trim($_POST['base_message_es']) : '';
    $base_en = isset($_POST['base_message_en']) ? trim($_POST['base_message_en']) : '';
    $lang = isset($_POST['default_language']) ? ($_POST['default_language'] === 'en' ? 'en' : 'es') : 'es';
    $emojis = isset($_POST['include_emojis']) ? (int)$_POST['include_emojis'] : 1;

    if ($base_es === '' || $base_en === '') {
        redirect('../index.php?view=whatsapp_settings&error=' . urlencode('Los mensajes base ES/EN son obligatorios'));
    }

    try {
        // Upsert settings row with id = 1
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_settings (id, base_message_es, base_message_en, default_language, include_emojis)
            VALUES (1, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                base_message_es = VALUES(base_message_es),
                base_message_en = VALUES(base_message_en),
                default_language = VALUES(default_language),
                include_emojis = VALUES(include_emojis)
        ");
        $stmt->execute([$base_es, $base_en, $lang, $emojis]);
        redirect('../index.php?view=whatsapp_settings&success=' . urlencode('Configuración guardada'));
    } catch (PDOException $e) {
        redirect('../index.php?view=whatsapp_settings&error=' . urlencode($e->getMessage()));
    }
}

redirect('../index.php?view=whatsapp_settings');
?>

