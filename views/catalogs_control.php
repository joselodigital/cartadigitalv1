<?php
requireRole('super_admin');

$total_products = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $total_products = (int)$stmt->fetch()['total'];
} catch (PDOException $e) {
    $total_products = 0;
}

$empty_catalogs = [];
try {
    $stmt = $pdo->query("
        SELECT b.id, b.name, b.slug
        FROM businesses b 
        LEFT JOIN products p ON p.business_id = b.id 
        WHERE p.id IS NULL
        ORDER BY b.name ASC
    ");
    $empty_catalogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $empty_catalogs = [];
}

$invalid_products = [];
try {
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.price, p.stock, b.id as business_id, b.name as business_name, b.slug as business_slug
        FROM products p
        JOIN businesses b ON b.id = p.business_id
        WHERE p.price <= 0 OR p.stock < 0
        ORDER BY b.name ASC, p.name ASC
    ");
    $invalid_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $invalid_products = [];
}

$heavy_images = [];
try {
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.image, b.id as business_id, b.name as business_name, b.slug as business_slug
        FROM products p
        JOIN businesses b ON b.id = p.business_id
        WHERE p.image IS NOT NULL AND p.image <> ''
        ORDER BY b.name ASC, p.name ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $path = 'public/uploads/' . $row['image'];
        if (file_exists($path)) {
            $size = filesize($path);
            if ($size !== false && $size > (2 * 1024 * 1024)) {
                $row['size_bytes'] = $size;
                $heavy_images[] = $row;
            }
        }
    }
} catch (PDOException $e) {
    $heavy_images = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Catálogos</title>
    <link rel="stylesheet" href="public/css/style.css">
    <script>
        function showTab(tabId) {
            var tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(function(t){ t.classList.remove('active'); });
            document.getElementById(tabId).classList.add('active');
            var btns = document.querySelectorAll('.tab-btn');
            btns.forEach(function(b){ b.classList.remove('active'); });
            var map = { 'tab-empty':'btn-empty', 'tab-heavy':'btn-heavy', 'tab-invalid':'btn-invalid' };
            document.getElementById(map[tabId]).classList.add('active');
        }
        document.addEventListener('DOMContentLoaded', function(){ showTab('tab-empty'); });
    </script>
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_super.php'; ?>

    <div class="content">
        <?php include 'views/partials/topbar_super.php'; ?>
        <h1>Control de Catálogos</h1>

        <div class="stats">
            <div class="stat-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><?php echo $total_products; ?></h3>
                <p>Productos Totales</p>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #f39c12 0%, #d35400 100%);">
                <h3><?php echo count($empty_catalogs); ?></h3>
                <p>Negocios sin productos</p>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                <h3><?php echo count($heavy_images); ?></h3>
                <p>Imágenes > 2MB</p>
            </div>
            <div class="stat-box" style="background: linear-gradient(135deg, #ff7675 0%, #e84393 100%);">
                <h3><?php echo count($invalid_products); ?></h3>
                <p>Productos inválidos</p>
            </div>
        </div>

        <div class="card">
            <div class="tabs">
                <button id="btn-empty" class="tab-btn" onclick="showTab('tab-empty')">Catálogos Vacíos</button>
                <button id="btn-heavy" class="tab-btn" onclick="showTab('tab-heavy')">Imágenes Pesadas</button>
                <button id="btn-invalid" class="tab-btn" onclick="showTab('tab-invalid')">Productos Inválidos</button>
            </div>

            <div id="tab-empty" class="tab-content">
                <h2>Negocios sin productos (<?php echo count($empty_catalogs); ?>)</h2>
                <?php if (count($empty_catalogs) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Negocio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empty_catalogs as $b): ?>
                        <tr>
                            <td><?php echo $b['id']; ?></td>
                            <td><?php echo htmlspecialchars($b['name']); ?></td>
                            <td>
                                <a class="btn btn-blue" href="index.php?view=stats_business&id=<?php echo $b['id']; ?>">Ver estadísticas</a>
                                <a class="btn btn-blue" style="background:#6c757d" href="<?php echo htmlspecialchars($b['slug']); ?>" target="_blank">Ver catálogo</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="padding:20px; text-align:center; color:#2e7d32; background:#e8f5e9; border-radius:4px;">Todos los negocios tienen productos.</p>
                <?php endif; ?>
            </div>

            <div id="tab-heavy" class="tab-content">
                <h2>Imágenes Pesadas (> 2MB) (<?php echo count($heavy_images); ?>)</h2>
                <?php if (count($heavy_images) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Producto</th>
                            <th>Nombre</th>
                            <th>Negocio</th>
                            <th>Imagen</th>
                            <th>Tamaño</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($heavy_images as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['business_name']); ?></td>
                            <td>
                                <span class="badge"><?php echo htmlspecialchars($row['image']); ?></span>
                            </td>
                            <td>
                                <?php 
                                $mb = round(($row['size_bytes'] / (1024 * 1024)), 2);
                                echo $mb . ' MB';
                                ?>
                            </td>
                            <td>
                                <a class="btn btn-blue" href="index.php?view=stats_business&id=<?php echo $row['business_id']; ?>">Ver estadísticas</a>
                                <a class="btn btn-blue" style="background:#6c757d" href="<?php echo htmlspecialchars($row['business_slug']); ?>" target="_blank">Ver catálogo</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="padding:20px; text-align:center; color:#2e7d32; background:#e8f5e9; border-radius:4px;">No se encontraron imágenes pesadas.</p>
                <?php endif; ?>
            </div>

            <div id="tab-invalid" class="tab-content">
                <h2>Productos inválidos (<?php echo count($invalid_products); ?>)</h2>
                <?php if (count($invalid_products) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID Producto</th>
                            <th>Nombre</th>
                            <th>Negocio</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invalid_products as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><?php echo htmlspecialchars($p['business_name']); ?></td>
                            <td style="color: <?php echo $p['price'] <= 0 ? 'red' : '#333'; ?>;"><?php echo $p['price']; ?></td>
                            <td style="color: <?php echo $p['stock'] < 0 ? 'red' : '#333'; ?>;"><?php echo isset($p['stock']) ? $p['stock'] : 0; ?></td>
                            <td>
                                <a class="btn btn-blue" href="index.php?view=stats_business&id=<?php echo $p['business_id']; ?>">Ver estadísticas</a>
                                <!-- Catalog link needs slug, but we don't have it here easily without join. 
                                     However, the redirect in catalog.php will handle ID->Slug. 
                                     Or we can modify query. Let's modify query first. -->
                                <a class="btn btn-blue" style="background:#6c757d" href="<?php echo htmlspecialchars($p['business_slug']); ?>" target="_blank">Ver catálogo</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="padding:20px; text-align:center; color:#2e7d32; background:#e8f5e9; border-radius:4px;">No se encontraron productos inválidos.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();
    </script>
</body>
</html>
