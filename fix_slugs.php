<?php
require_once 'config/db.php';

function generateSlug($string) {
    // Remove accents
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    // Lowercase
    $string = strtolower($string);
    // Replace non-alphanumeric with hyphen
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    // Remove leading/trailing hyphens
    $string = trim($string, '-');
    return $string;
}

$stmt = $pdo->query("SELECT id, name, slug FROM businesses");
$businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Checking slugs...\n";

foreach ($businesses as $b) {
    if (empty($b['slug'])) {
        $baseSlug = generateSlug($b['name']);
        $slug = $baseSlug;
        $counter = 1;
        
        // Ensure uniqueness
        while (true) {
            $check = $pdo->prepare("SELECT COUNT(*) FROM businesses WHERE slug = ? AND id != ?");
            $check->execute([$slug, $b['id']]);
            if ($check->fetchColumn() == 0) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        $update = $pdo->prepare("UPDATE businesses SET slug = ? WHERE id = ?");
        $update->execute([$slug, $b['id']]);
        echo "Updated Business ID {$b['id']}: {$b['name']} -> {$slug}\n";
    } else {
        echo "Business ID {$b['id']} already has slug: {$b['slug']}\n";
    }
}
echo "Done.\n";
?>