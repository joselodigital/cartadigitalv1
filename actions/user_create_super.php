<?php
// actions/user_create_super.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $role_id = intval($_POST['role_id']);
    $business_id = intval($_POST['business_id']);

    if (empty($name) || empty($email) || empty($password) || empty($role_id) || empty($business_id)) {
        redirect('../index.php?view=users_list&error=Todos los campos son requeridos');
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        redirect('../index.php?view=users_list&error=El email ya está registrado');
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (business_id, role_id, name, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$business_id, $role_id, $name, $email, $hashed_password]);
        
        $new_id = $pdo->lastInsertId();
        logAction($_SESSION['user_id'], 'create_user', "Creado usuario ID: $new_id ($email)");

        redirect('../index.php?view=users_list&success=Usuario creado exitosamente');
    } catch (PDOException $e) {
        redirect('../index.php?view=users_list&error=' . urlencode($e->getMessage()));
    }
}
?>
