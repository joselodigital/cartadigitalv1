<?php
// views/dashboard_collab.php
requireRole('colaborador');

$business_id = $_SESSION['business_id'];

// Fetch Business Info
$stmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
$stmt->execute([$business_id]);
$business = $stmt->fetch();

// Fetch Products with Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total products for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE business_id = ?");
$stmt->execute([$business_id]);
$total_products_pagination = $stmt->fetch()['total'];
$total_pages = ceil($total_products_pagination / $limit);

$stmt = $pdo->prepare("SELECT * FROM products WHERE business_id = :business_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':business_id', $business_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Colaborador</title>
    <link rel="stylesheet" href="public/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <style>
        /* Specific styles for collab dashboard if any */
    </style>
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_collab.php'; ?>

    <div class="content">
        <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
            <button id="sidebar-toggle" class="sidebar-toggle">
                <i data-feather="menu"></i>
            </button>
            <h1 style="margin:0;">Panel de Colaborador</h1>
        </div>

        <div class="card">
            <h2>Reportar problema al Super Admin</h2>
            <?php if(isset($_GET['success'])): ?>
                <div class="alert success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <form action="actions/ticket_create.php" method="POST">
                <label>Asunto:</label>
                <input type="text" name="subject" required>
                <label>Mensaje:</label>
                <textarea name="message" rows="4" required></textarea>
                <button type="submit" class="btn btn-blue" style="margin-top:10px;">Enviar Ticket</button>
            </form>
        </div>

        <div id="products" class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Productos del Negocio</h2>
                <button onclick="document.getElementById('modal-product').style.display='block'" class="btn btn-green">
                    <i data-feather="plus"></i> Nuevo Producto
                </button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <?php if($p['image']): ?>
                                    <img src="public/uploads/<?php echo $p['image']; ?>" width="50" style="border-radius:4px;">
                                <?php else: ?>
                                    <div style="width:50px; height:50px; background:#f1f5f9; border-radius:4px; display:flex; align-items:center; justify-content:center;">
                                        <i data-feather="image" style="width:20px; color:#cbd5e1;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                <div style="font-size:0.8rem; color:#64748b;">Stock: <?php echo $p['stock'] ?? 0; ?></div>
                            </td>
                            <td>$<?php echo number_format($p['price'], 2); ?></td>
                            <td style="text-align:right;">
                                <button onclick="openEditProduct(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['description'] ?? '', ENT_QUOTES); ?>', '<?php echo $p['price']; ?>', '<?php echo $p['stock'] ?? 0; ?>')" class="btn-icon" style="color:#2563eb; margin-right:5px; background:none; border:none; cursor:pointer;">
                                    <i data-feather="edit-2" style="width:16px;"></i>
                                </button>
                                <a href="actions/product_delete.php?id=<?php echo $p['id']; ?>" onclick="return confirm('¿Eliminar este producto?')" class="btn-icon" style="color:#ef4444; text-decoration:none; display:inline-block;">
                                    <i data-feather="trash-2" style="width:16px;"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container" style="display:flex; justify-content:center; align-items:center; gap:10px; margin-top:20px;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-sm btn-secondary" style="display:flex; align-items:center; gap:5px;">
                        <i data-feather="chevron-left" style="width:16px;"></i> Anterior
                    </a>
                <?php endif; ?>
                
                <span style="font-weight:600; color:var(--text-secondary); font-size:0.9rem;">
                    Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                </span>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-sm btn-secondary" style="display:flex; align-items:center; gap:5px;">
                        Siguiente <i data-feather="chevron-right" style="width:16px;"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Create Product -->
    <div id="modal-product" class="modal">
        <div class="modal-content">
            <span onclick="document.getElementById('modal-product').style.display='none'" style="float:right; cursor:pointer;">&times;</span>
            <h2>Nuevo Producto</h2>
            <form action="actions/product_create.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="name" placeholder="Nombre del Producto" required>
                <textarea name="description" placeholder="Descripción"></textarea>
                <input type="number" step="0.01" name="price" placeholder="Precio" required>
                <label>Stock:</label>
                <input type="number" name="stock" placeholder="Stock" min="0" value="0" required>
                <label>Imagen:</label>
                <input type="file" name="image" accept="image/*">
                <button type="submit" class="btn btn-green">Guardar</button>
            </form>
        </div>
    </div>

    <!-- Modal Edit Product -->
    <div id="modal-edit-product" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modal-edit-product').style.display='none'">&times;</span>
            <h2>Editar Producto</h2>
            <form action="actions/product_update.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit-prod-id">
                <label>Nombre:</label>
                <input type="text" name="name" id="edit-prod-name" required>
                <label>Descripción:</label>
                <textarea name="description" id="edit-prod-desc"></textarea>
                <label>Precio:</label>
                <input type="number" step="0.01" name="price" id="edit-prod-price" required>
                <label>Stock:</label>
                <input type="number" name="stock" id="edit-prod-stock" min="0" required>
                <label>Imagen (Opcional - dejar vacío para mantener actual):</label>
                <input type="file" name="image" accept="image/*">
                <button type="submit" class="btn btn-blue">Actualizar</button>
            </form>
        </div>
    </div>

    <script>
        feather.replace();

        function openEditProduct(id, name, desc, price, stock) {
            document.getElementById('edit-prod-id').value = id;
            document.getElementById('edit-prod-name').value = name;
            document.getElementById('edit-prod-desc').value = desc;
            document.getElementById('edit-prod-price').value = price;
            document.getElementById('edit-prod-stock').value = stock;
            document.getElementById('modal-edit-product').style.display = 'block';
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        }

        // Sidebar Toggle Logic
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
            });
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                });
            }
        }
    </script>
</body>
</html>
