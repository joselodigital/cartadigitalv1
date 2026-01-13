<?php
// actions/collab_create.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('admin_negocio');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_SESSION['business_id'];
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];

    // Role ID 3 = collaborator
    $role_id = 3;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (business_id, role_id, name, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$business_id, $role_id, $name, $email, $hashed_password]);
        $new_id = $pdo->lastInsertId();
        logAction($_SESSION['user_id'], 'create_user', "Creado colaborador ID: $new_id ($email) para negocio ID: $business_id");
        redirect('../index.php?view=dashboard_business&success=Colaborador creado');
    } catch (PDOException $e) {
        redirect('../index.php?view=dashboard_business&error=' . urlencode($e->getMessage()));
    }
}
?>
