<?php
// actions/track_whatsapp.php
require_once '../config/db.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$business_id = isset($_GET['business_id']) ? (int)$_GET['business_id'] : 0;

if ($product_id && $business_id) {
    // 1. Record the click
    try {
        $stmt = $pdo->prepare("INSERT INTO whatsapp_clicks (business_id, product_id) VALUES (?, ?)");
        $stmt->execute([$business_id, $product_id]);
    } catch (Exception $e) {
        // Silently fail logging if DB error, so user flow isn't interrupted
        error_log("Tracking Error: " . $e->getMessage());
    }

    // 2. Fetch info to build WhatsApp link
    $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT name, whatsapp FROM businesses WHERE id = ?");
    $stmt->execute([$business_id]);
    $business = $stmt->fetch();

    if ($product && $business && !empty($business['whatsapp'])) {
        // Load global whatsapp settings
        $settings = [
            'base_message_es' => 'Hola, te escribo desde el catálogo de {negocio}. Me interesa {producto} (Precio: ${precio}).',
            'base_message_en' => 'Hello, I am messaging from the catalog of {business}. I am interested in {product} (Price: ${price}).',
            'default_language' => 'es',
            'include_emojis' => 1
        ];
        try {
            $stmt = $pdo->query("SELECT base_message_es, base_message_en, default_language, include_emojis FROM whatsapp_settings WHERE id = 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $settings = $row;
            }
        } catch (Exception $e) {
            // fallback to defaults
        }

        $language = isset($settings['default_language']) ? $settings['default_language'] : 'es';
        $include_emojis = isset($settings['include_emojis']) ? (int)$settings['include_emojis'] : 1;
        $template = $language === 'en' ? $settings['base_message_en'] : $settings['base_message_es'];
        
        // Build replacements
        $replacements = [
            '{negocio}' => $business['name'],
            '{business}' => $business['name'],
            '{producto}' => $product['name'],
            '{product}' => $product['name'],
            '{precio}' => number_format($product['price'], 2),
            '{price}' => number_format($product['price'], 2),
        ];
        $msg = strtr($template, $replacements);
        
        if ($include_emojis) {
            $msg .= " 🛍️🤝";
        }
        $link = "https://wa.me/" . $business['whatsapp'] . "?text=" . urlencode($msg);
        
        // Redirect to WhatsApp
        header("Location: " . $link);
        exit;
    }
}

// Fallback if something is wrong
header("Location: ../index.php");
exit;
