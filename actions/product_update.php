<?php
// actions/product_update.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole(['admin_negocio', 'colaborador']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $business_id = $_SESSION['business_id'];
    $name = clean($_POST['name']);
    $description = clean($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;

    // Determine redirect URL based on role
    $redirect_url = ($_SESSION['role'] === 'colaborador') 
        ? '../index.php?view=dashboard_collab' 
        : '../index.php?view=dashboard_business';

    // Validations (Enforcement)
    if (empty($name) || $price <= 0) {
        redirect($redirect_url . '&error=' . urlencode('Nombre y Precio mayor a 0 son obligatorios'));
    }

    if ($stock < 0) {
        redirect($redirect_url . '&error=' . urlencode('El stock no puede ser negativo'));
    }

    // Verify ownership
    $stmt = $pdo->prepare("SELECT id, image FROM products WHERE id = ? AND business_id = ?");
    $stmt->execute([$id, $business_id]);
    $product = $stmt->fetch();

    if (!$product) {
        redirect($redirect_url . '&error=Producto no encontrado');
    }
    
    $image_name = $product['image'];
    
    // Handle Image Upload if new image provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $max_size = 2 * 1024 * 1024; // 2MB
        if ($_FILES['image']['size'] > $max_size) {
            redirect($redirect_url . '&error=' . urlencode('La imagen es demasiado pesada (Máx 2MB)'));
        }

        $upload_dir = '../public/uploads/';
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        // Basic extension validation
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array(strtolower($ext), $allowed)) {
             redirect($redirect_url . '&error=' . urlencode('Formato de imagen no permitido'));
        }

        $new_image_name = uniqid() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_image_name)) {
             // Delete old image if exists
             if ($image_name && file_exists($upload_dir . $image_name)) {
                 unlink($upload_dir . $image_name);
             }
             $image_name = $new_image_name;
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $stock, $image_name, $id]);
        redirect($redirect_url . '&success=Producto actualizado');
    } catch (PDOException $e) {
        redirect($redirect_url . '&error=' . urlencode($e->getMessage()));
    }
}
?>