<?php
// actions/user_update_super.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $role_id = intval($_POST['role_id']);
    $business_id = intval($_POST['business_id']);
    $password = $_POST['password']; // Optional

    if (empty($name) || empty($email) || empty($role_id) || empty($business_id)) {
        redirect('../index.php?view=users_list&error=Campos obligatorios faltantes');
    }

    // Check if email taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        redirect('../index.php?view=users_list&error=El email ya está en uso por otro usuario');
    }

    try {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role_id = ?, business_id = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role_id, $business_id, $hashed_password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role_id = ?, business_id = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role_id, $business_id, $id]);
        }
        
        logAction($_SESSION['user_id'], 'update_user', "Actualizado usuario ID: $id ($email)");

        redirect('../index.php?view=users_list&success=Usuario actualizado exitosamente');
    } catch (PDOException $e) {
        redirect('../index.php?view=users_list&error=' . urlencode($e->getMessage()));
    }
}
?>
