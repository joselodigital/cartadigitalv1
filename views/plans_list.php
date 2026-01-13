<?php
// views/plans_list.php
requireRole('super_admin');

// Fetch all plans
$stmt = $pdo->query("SELECT * FROM plans ORDER BY price ASC");
$plans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Planes y Suscripciones</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background-color: var(--bg-card);
            color: var(--text-main);
            margin: 5% auto;
            padding: 24px;
            border: 1px solid var(--border-color);
            width: 500px;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
        }
        .close {
            color: var(--text-muted);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close:hover { color: var(--text-main); }
        
        .feature-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin: 2px;
        }
        .badge-green { background: #d4edda; color: #155724; }
        .badge-red { background: #f8d7da; color: #721c24; }
        .badge-blue { background: #cce5ff; color: #004085; }
        .badge-gray { background: #e2e3e5; color: #383d41; }
        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-active { background-color: #28a745; }
        .status-inactive { background-color: #dc3545; }
    </style>
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_super.php'; ?>

    <div class="content">
        <?php include 'views/partials/topbar_super.php'; ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Gestión de Planes y Suscripciones</h1>
            <button onclick="document.getElementById('createModal').style.display='block'" class="btn btn-green">+ Nuevo Plan</button>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
            <div class="alert error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Precio (S/)</th>
                        <th>Límite</th>
                        <th>Características</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $p): ?>
                        <tr style="<?php echo $p['is_hidden'] ? 'opacity: 0.6; background-color: var(--bg-body);' : ''; ?>">
                            <td>
                                <?php if($p['is_hidden']): ?>
                                    <span class="status-dot status-inactive" title="Oculto"></span> Oculto
                                <?php else: ?>
                                    <span class="status-dot status-active" title="Activo"></span> Activo
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($p['slug']); ?></code></td>
                            <td><?php echo number_format($p['price'], 2); ?></td>
                            <td><?php echo $p['product_limit'] > 9999 ? 'Ilimitado' : $p['product_limit']; ?></td>
                            <td>
                                <?php if($p['branding_hidden']): ?>
                                    <span class="feature-badge badge-blue">Sin Branding</span>
                                <?php endif; ?>
                                <?php if($p['allow_private_catalog']): ?>
                                    <span class="feature-badge badge-green">Privado</span>
                                <?php endif; ?>
                                <?php if($p['custom_domain']): ?>
                                    <span class="feature-badge badge-green">Dominio</span>
                                <?php endif; ?>
                                <?php if($p['ads_enabled']): ?>
                                    <span class="feature-badge badge-green">Ads</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($p)); ?>)" class="btn btn-blue">Editar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('createModal').style.display='none'">&times;</span>
            <h2>Nuevo Plan</h2>
            <form action="actions/plan_create.php" method="POST">
                <label>Nombre:</label>
                <input type="text" name="name" required style="width:100%; padding:8px; margin:5px 0;">
                
                <label>Precio (S/):</label>
                <input type="number" step="0.01" name="price" required style="width:100%; padding:8px; margin:5px 0;">

                <label>Límite de Productos (999999 para ilimitado):</label>
                <input type="number" name="product_limit" required style="width:100%; padding:8px; margin:5px 0;">

                <div style="margin-top:15px; background-color: var(--bg-body); padding:10px; border-radius:5px;">
                    <label style="display:block; margin-bottom:10px;">
                        <input type="checkbox" name="branding_hidden" value="1"> 
                        <strong>Ocultar Branding</strong> (Marca blanca)
                    </label>

                    <label style="display:block; margin-bottom:10px;">
                        <input type="checkbox" name="allow_private_catalog" value="1"> 
                        <strong>Catálogo Privado</strong> (Acceso con contraseña)
                    </label>

                    <label style="display:block; margin-bottom:10px;">
                        <input type="checkbox" name="custom_domain" value="1"> 
                        <strong>Dominio Propio</strong>
                    </label>

                    <label style="display:block; margin-bottom:10px;">
                        <input type="checkbox" name="ads_enabled" value="1"> 
                        <strong>Publicidad en Redes</strong>
                    </label>

                    <label style="display:block;">
                        <input type="checkbox" name="is_hidden" value="1"> 
                        <strong style="color:red;">Ocultar Plan</strong> (No seleccionable)
                    </label>
                </div>

                <button type="submit" class="btn btn-green" style="width:100%; margin-top:15px;">Crear Plan</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Editar Plan</h2>
            <form action="actions/plan_update.php" method="POST">
                <input type="hidden" name="id" id="edit-id">
                
                <label>Nombre:</label>
                <input type="text" name="name" id="edit-name" required style="width:100%; padding:8px; margin:5px 0;">
                
                <label>Precio (S/):</label>
                <input type="number" step="0.01" name="price" id="edit-price" required style="width:100%; padding:8px; margin:5px 0;">

                <label>Límite de Productos (999999 para ilimitado):</label>
                <input type="number" name="product_limit" id="edit-limit" required style="width:100%; padding:8px; margin:5px 0;">

                <div style="margin-top:15px; background-color: var(--bg-body); padding:10px; border-radius:5px;">
                    <label style="display:block; margin-bottom:10px;">
                        <input type="checkbox" name="branding_hidden" id="edit-branding" value="1"> 
                        <strong>Ocultar Branding</strong> (Marca blanca)
                    </label>

                    <label style="display:block; margin-bottom:10px;">
                        <input type="checkbox" name="allow_private_catalog" id="edit-private" value="1"> 
                        <strong>Catálogo Privado</strong> (Acceso con contraseña)
                    </label>

                    <label style="display:block; margin-bottom:10px;">
                        <input type="checkbox" name="custom_domain" id="edit-domain" value="1"> 
                        <strong>Dominio Propio</strong>
                    </label>

                    <label style="display:block; margin-bottom:10px;">
                        <input type="checkbox" name="ads_enabled" id="edit-ads" value="1"> 
                        <strong>Publicidad en Redes</strong>
                    </label>

                    <label style="display:block;">
                        <input type="checkbox" name="is_hidden" id="edit-hidden" value="1"> 
                        <strong style="color:red;">Ocultar Plan</strong> (No seleccionable)
                    </label>
                </div>

                <button type="submit" class="btn btn-blue" style="width:100%; margin-top:15px;">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(plan) {
            document.getElementById('edit-id').value = plan.id;
            document.getElementById('edit-name').value = plan.name;
            document.getElementById('edit-price').value = plan.price;
            document.getElementById('edit-limit').value = plan.product_limit;
            document.getElementById('edit-branding').checked = plan.branding_hidden == 1;
            document.getElementById('edit-private').checked = plan.allow_private_catalog == 1;
            document.getElementById('edit-domain').checked = plan.custom_domain == 1;
            document.getElementById('edit-ads').checked = plan.ads_enabled == 1;
            document.getElementById('edit-hidden').checked = plan.is_hidden == 1;
            document.getElementById('editModal').style.display = 'block';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('createModal')) {
                document.getElementById('createModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>
