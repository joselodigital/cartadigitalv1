<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'catalogodigital';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Base de datos '$dbname' creada o ya existente.<br>";

    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read and execute schema.sql
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    
    // Split by semicolon to execute statements individually if needed, 
    // but PDO::exec can handle multiple queries if emulation is enabled (default in some versions)
    // or we can just run it. However, some drivers don't support multiple queries in one call.
    // Let's split strictly.
    
    // Remove comments
    $lines = explode("\n", $sql);
    $cleanSql = "";
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line && strpos($line, '--') !== 0) {
            $cleanSql .= $line . "\n";
        }
    }

    $statements = array_filter(array_map('trim', explode(';', $cleanSql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }

    echo "Tablas y datos iniciales importados correctamente.<br>";

} catch (PDOException $e) {
    die("Error de conexión o ejecución: " . $e->getMessage());
}
?>