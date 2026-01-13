<?php
// actions/product_create.php
require_once '../config/db.php';
require_once '../includes/functions.php';

requireRole(['admin_negocio', 'colaborador']);

// Check Plan Limits
$business_id = $_SESSION['business_id'];

// Fetch Business Plan and Count in one go? Or better logic:
// Fetch current plan slug from business
$stmt = $pdo->prepare("SELECT plan FROM businesses WHERE id = ?");
$stmt->execute([$business_id]);
$plan_slug = $stmt->fetchColumn();

// Fetch limits from plans table
$stmt = $pdo->prepare("SELECT product_limit FROM plans WHERE slug = ?");
$stmt->execute([$plan_slug]);
$limit = $stmt->fetchColumn();

// Fallback if plan not found
if ($limit === false) {
    $limit = 15; // Default fallback
}

    // Determine redirect URL based on role
    $redirect_url = ($_SESSION['role'] === 'colaborador') 
        ? '../index.php?view=dashboard_collab' 
        : '../index.php?view=dashboard_business';

    // Count products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE business_id = ?");
    $stmt->execute([$business_id]);
    $current_count = $stmt->fetchColumn();

    if ($current_count >= $limit) {
        redirect($redirect_url . '&error=' . urlencode("Has alcanzado el límite de productos de tu plan ($plan_slug: $limit)"));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = clean($_POST['name']);
        $description = clean($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        
        // Validations (Enforcement)
        if (empty($name) || $price <= 0) {
            redirect($redirect_url . '&error=' . urlencode('Nombre y Precio mayor a 0 son obligatorios'));
        }
        
        if ($stock < 0) {
            redirect($redirect_url . '&error=' . urlencode('El stock no puede ser negativo'));
        }
        
        $image_name = '';
        
        // Handle Image Upload with Max Size Limit (e.g., 2MB)
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
    
            $image_name = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
        }
    
        try {
            $stmt = $pdo->prepare("INSERT INTO products (business_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$business_id, $name, $description, $price, $stock, $image_name]);
            redirect($redirect_url . '&success=Producto creado');
        } catch (PDOException $e) {
            redirect($redirect_url . '&error=' . urlencode($e->getMessage()));
        }
    }
?>