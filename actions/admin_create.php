<?php
// actions/admin_create.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'];
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password) || empty($business_id)) {
        redirect('../index.php?view=dashboard_super&error=Todos los campos son requeridos');
    }

    // Role ID for admin_negocio is 2 (from schema.sql)
    $role_id = 2;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (business_id, role_id, name, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$business_id, $role_id, $name, $email, $hashed_password]);
        $new_id = $pdo->lastInsertId();
        logAction($_SESSION['user_id'], 'create_user', "Creado Admin Negocio ID: $new_id ($email) para negocio ID: $business_id");
        redirect('../index.php?view=dashboard_super&success=Administrador creado');
    } catch (PDOException $e) {
        redirect('../index.php?view=dashboard_super&error=' . urlencode($e->getMessage()));
    }
}
?>
