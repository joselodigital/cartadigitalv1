<?php
// views/users_list.php
requireRole('super_admin');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Count total users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];
$total_pages = ceil($total_users / $limit);

// Fetch users with role and business info (Paginated)
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.status, u.created_at, u.last_login, u.last_ip, r.name as role_name, r.id as role_id, b.name as business_name, b.id as business_id
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN businesses b ON u.business_id = b.id
    ORDER BY u.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

// Fetch Roles for dropdown
$stmt = $pdo->query("SELECT * FROM roles WHERE id != 1"); // Exclude Super Admin for now
$roles = $stmt->fetchAll();

// Fetch Businesses for dropdown
$stmt = $pdo->query("SELECT * FROM businesses ORDER BY name ASC");
$businesses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Super Admin</title>
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
            margin: 10% auto;
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
    </style>
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_super.php'; ?>

    <div class="content">
        <?php include 'views/partials/topbar_super.php'; ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Gestión de Usuarios</h1>
            <button onclick="openCreateModal()" class="btn btn-green">+ Nuevo Usuario</button>
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
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Rol</th>
                        <th>Negocio Asignado</th>
                        <th>Último Acceso</th>
                        <th>IP</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <?php if($u['status'] === 'blocked'): ?>
                                    <span style="color:red; font-weight:bold;">🚫 Bloqueado</span>
                                <?php else: ?>
                                    <span style="color:green; font-weight:bold;">✅ Activo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    if($u['role_name'] == 'super_admin') echo '<span style="color:#a855f7;font-weight:bold">Super Admin</span>';
                                    elseif($u['role_name'] == 'admin_negocio') echo '<span style="color:#3b82f6;font-weight:bold">Admin Negocio</span>';
                                    else echo '<span>Personal / Colaborador</span>';
                                ?>
                            </td>
                            <td>
                                <?php echo $u['business_name'] ? htmlspecialchars($u['business_name']) : '-'; ?>
                            </td>
                            <td>
                                <?php echo $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '<span style="color:gray">Nunca</span>'; ?>
                            </td>
                            <td>
                                <?php echo $u['last_ip'] ? htmlspecialchars($u['last_ip']) : '-'; ?>
                            </td>
                            <td>
                                <?php if($u['role_name'] != 'super_admin'): ?>
                                <div style="display:flex; gap:5px; flex-wrap:nowrap; white-space: nowrap;">
                                    <button onclick="openEditModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name']); ?>', '<?php echo htmlspecialchars($u['email']); ?>', <?php echo $u['role_id']; ?>, <?php echo $u['business_id'] ? $u['business_id'] : 'null'; ?>)" class="btn btn-sm btn-blue" style="padding: 6px;" title="Editar / Resetear Password">✏️</button>
                                    
                                    <?php if($u['status'] === 'active'): ?>
                                        <a href="actions/user_status.php?id=<?php echo $u['id']; ?>&status=blocked" class="btn btn-sm btn-red" style="padding: 6px;" onclick="return confirm('¿Bloquear acceso a este usuario?')" title="Bloquear Acceso">🔒</a>
                                    <?php else: ?>
                                        <a href="actions/user_status.php?id=<?php echo $u['id']; ?>&status=active" class="btn btn-sm btn-green" style="padding: 6px;" title="Desbloquear Acceso">🔓</a>
                                    <?php endif; ?>
                                    
                                    <a href="index.php?view=audit_log&user_id=<?php echo $u['id']; ?>" class="btn btn-sm btn-blue" style="background:#17a2b8; padding: 6px;" title="Ver Historial">📜</a>
                                    
                                    <a href="actions/user_delete.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-red" style="padding: 6px;" onclick="return confirm('¿Seguro que deseas eliminar este usuario?')" title="Eliminar Usuario">🗑️</a>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px;">
                <?php if ($page > 1): ?>
                    <a href="?view=users_list&page=<?php echo $page - 1; ?>" class="btn btn-sm btn-blue">Anterior</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?view=users_list&page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-green' : 'btn-blue'; ?>" style="<?php echo $i === $page ? 'pointer-events:none;' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?view=users_list&page=<?php echo $page + 1; ?>" class="btn btn-sm btn-blue">Siguiente</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCreateModal()">&times;</span>
            <h2>Nuevo Usuario</h2>
            <form action="actions/user_create_super.php" method="POST">
                <label>Nombre:</label>
                <input type="text" name="name" required style="width:100%; padding:8px; margin:5px 0;">
                
                <label>Email:</label>
                <input type="email" name="email" required style="width:100%; padding:8px; margin:5px 0;">

                <label>Contraseña:</label>
                <input type="password" name="password" required style="width:100%; padding:8px; margin:5px 0;">

                <label>Rol:</label>
                <select name="role_id" required style="width:100%; padding:8px; margin:5px 0;">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>">
                            <?php echo $r['name'] == 'admin_negocio' ? 'Admin de Negocio' : 'Personal / Colaborador'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Asignar a Negocio:</label>
                <select name="business_id" required style="width:100%; padding:8px; margin:5px 0;">
                    <option value="">Seleccione un negocio...</option>
                    <?php foreach ($businesses as $b): ?>
                        <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-green" style="width:100%; margin-top:15px;">Crear Usuario</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Editar Usuario</h2>
            <form action="actions/user_update_super.php" method="POST">
                <input type="hidden" name="id" id="edit-id">
                
                <label>Nombre:</label>
                <input type="text" name="name" id="edit-name" required style="width:100%; padding:8px; margin:5px 0;">
                
                <label>Email:</label>
                <input type="email" name="email" id="edit-email" required style="width:100%; padding:8px; margin:5px 0;">

                <label>Nueva Contraseña (dejar en blanco para no cambiar):</label>
                <input type="password" name="password" style="width:100%; padding:8px; margin:5px 0;">

                <label>Rol:</label>
                <select name="role_id" id="edit-role" required style="width:100%; padding:8px; margin:5px 0;">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>">
                            <?php echo $r['name'] == 'admin_negocio' ? 'Admin de Negocio' : 'Personal / Colaborador'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Negocio:</label>
                <select name="business_id" id="edit-business" required style="width:100%; padding:8px; margin:5px 0;">
                    <option value="">Seleccione un negocio...</option>
                    <?php foreach ($businesses as $b): ?>
                        <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-blue" style="width:100%; margin-top:15px;">Actualizar Usuario</button>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }
        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function openEditModal(id, name, email, role_id, business_id) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-role').value = role_id;
            document.getElementById('edit-business').value = business_id;
            document.getElementById('editModal').style.display = 'block';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('createModal')) {
                closeCreateModal();
            }
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
