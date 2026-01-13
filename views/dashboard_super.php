<?php
// views/dashboard_super.php
requireRole('super_admin');

// Logic to fetch data
$stmt = $pdo->query("SELECT COUNT(*) as total FROM businesses");
$total_businesses = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

// WhatsApp Clicks Stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_clicks");
$total_clicks = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM whatsapp_clicks WHERE MONTH(clicked_at) = MONTH(CURRENT_DATE()) AND YEAR(clicked_at) = YEAR(CURRENT_DATE())");
$month_clicks = $stmt->fetch()['total'];

// Alert: Inactive Businesses (No clicks in last 30 days)
$stmt = $pdo->query("
    SELECT b.id, b.name, b.whatsapp, 
           (SELECT MAX(clicked_at) FROM whatsapp_clicks WHERE business_id = b.id) as last_click
    FROM businesses b 
    WHERE b.status = 'active' 
    AND b.id NOT IN (
        SELECT DISTINCT business_id 
        FROM whatsapp_clicks 
        WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    )
");
$inactive_businesses_alert = $stmt->fetchAll();
$inactive_alert_count = count($inactive_businesses_alert);

// Stats Logic
$stmt = $pdo->query("SELECT COUNT(*) FROM businesses WHERE status = 'active'");
$active_count = $stmt->fetchColumn();
$inactive_count = $total_businesses - $active_count;

$total_biz = $total_businesses;
$active_pct = $total_biz > 0 ? ($active_count / $total_biz) * 100 : 0;
$inactive_pct = $total_biz > 0 ? ($inactive_count / $total_biz) * 100 : 0;

// Pagination for Businesses
$page_biz = isset($_GET['page_biz']) ? (int)$_GET['page_biz'] : 1;
$limit_biz = 5;
$offset_biz = ($page_biz - 1) * $limit_biz;
$total_pages_biz = ceil($total_businesses / $limit_biz);

$stmt = $pdo->prepare("SELECT * FROM businesses ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit_biz, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset_biz, PDO::PARAM_INT);
$stmt->execute();
$businesses = $stmt->fetchAll();

// Pagination for Users
$page_users = isset($_GET['page_users']) ? (int)$_GET['page_users'] : 1;
$limit_users = 5;
$offset_users = ($page_users - 1) * $limit_users;
$total_pages_users = ceil($total_users / $limit_users);

// Fetch Users with Role and Business info (Paginated)
$stmt = $pdo->prepare("
    SELECT u.*, r.name as role_name, b.name as business_name, b.slug as business_slug 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    LEFT JOIN businesses b ON u.business_id = b.id 
    ORDER BY u.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit_users, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset_users, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Super Admin</title>
    <link rel="stylesheet" href="public/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_super.php'; ?>

    <div class="content">
        <?php include 'views/partials/topbar_super.php'; ?>
        
        <div class="dashboard-header">
            <div>
                <h1>Panel de Control</h1>
                <p class="dashboard-welcome">Bienvenido al centro de administración.</p>
            </div>
            <div class="text-right">
                <span class="text-muted" style="font-size:0.9rem;"><?php echo date('d M Y'); ?></span>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <div class="d-flex justify-between items-start">
                    <div>
                        <h3><?php echo $total_businesses; ?></h3>
                        <p>Negocios</p>
                    </div>
                    <i data-feather="briefcase" class="icon-md icon-opacity"></i>
                </div>
            </div>
            <div class="stat-box users">
                <div class="d-flex justify-between items-start">
                    <div>
                        <h3><?php echo $total_users; ?></h3>
                        <p>Usuarios</p>
                    </div>
                    <i data-feather="users" class="icon-md icon-opacity"></i>
                </div>
            </div>
            <div class="stat-box whatsapp">
                <div class="d-flex justify-between items-start">
                    <div>
                        <h3><?php echo $total_clicks; ?></h3>
                        <p>Pedidos WhatsApp</p>
                        <span style="font-size: 0.8rem; opacity: 0.9;">+<?php echo $month_clicks; ?> este mes</span>
                    </div>
                    <i data-feather="message-circle" class="icon-md icon-opacity"></i>
                </div>
            </div>
            <div class="stat-box inactive" onclick="document.getElementById('modal-inactive').style.display='block'">
                <div class="d-flex justify-between items-start">
                    <div>
                        <h3><?php echo $inactive_alert_count; ?></h3>
                        <p>Inactivos (30d)</p>
                        <span style="font-size: 0.8rem; text-decoration: underline;">Ver detalles</span>
                    </div>
                    <i data-feather="alert-circle" class="icon-md icon-opacity"></i>
                </div>
            </div>
        </div>

        <div class="card" id="businesses">
            <div class="d-flex justify-between items-center mb-4">
                <h2 class="mt-0 mb-0">Negocios Registrados</h2>
                <div class="d-flex gap-2">
                    <a href="actions/export_businesses.php" class="btn btn-blue" style="background:var(--bg-body); color:var(--text-main); border:1px solid var(--border-color);">
                        <i data-feather="download"></i> Exportar
                    </a>
                    <button onclick="document.getElementById('modal-create').style.display='block'" class="btn btn-green">
                        <i data-feather="plus"></i> Nuevo Negocio
                    </button>
                </div>
            </div>
            
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:800px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Negocio</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>WhatsApp</th>
                            <th>Registro</th>
                            <th>Admin</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($businesses as $b): ?>
                            <?php
                                // Get admin for this business
                                $stmt_admin = $pdo->prepare("SELECT name FROM users WHERE business_id = ? AND role_id = 2");
                                $stmt_admin->execute([$b['id']]);
                                $admins = $stmt_admin->fetchAll(PDO::FETCH_COLUMN);
                                $admin_names = implode(", ", $admins);
                                
                                $planClass = 'plan-basico';
                                switch($b['plan']) {
                                    case 'pro': $planClass = 'plan-pro'; break;
                                    case 'premium': $planClass = 'plan-premium'; break;
                                    case 'enterprise': $planClass = 'plan-enterprise'; break;
                                }
                            ?>
                            <tr>
                                <td class="text-muted">#<?php echo $b['id']; ?></td>
                                <td>
                                    <div class="business-info">
                                        <?php if($b['logo']): ?>
                                            <img src="public/uploads/<?php echo $b['logo']; ?>" class="business-avatar">
                                        <?php else: ?>
                                            <div class="business-avatar-placeholder">
                                                <?php echo substr($b['name'], 0, 1); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="business-name"><?php echo htmlspecialchars($b['name']); ?></div>
                                            <?php if(!empty($b['internal_notes'])): ?>
                                                <div class="business-meta">
                                                    <i data-feather="file-text" style="width:10px; height:10px;"></i> Nota
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $planClass; ?> plan-badge">
                                        <?php echo strtoupper($b['plan'] ? $b['plan'] : 'BASICO'); ?>
                                    </span>
                                    <?php if ($b['subscription_end']): ?>
                                        <div class="text-muted" style="font-size:0.7rem; margin-top:2px;">
                                            Vence: <?php echo date('d/m/y', strtotime($b['subscription_end'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($b['status'] === 'active'): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php elseif($b['status'] === 'suspended'): ?>
                                        <span class="badge badge-warning">Suspendido</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $b['whatsapp']); ?>" target="_blank" class="text-muted" style="text-decoration:none; display:flex; align-items:center; gap:5px;">
                                        <i data-feather="message-circle" style="width:14px; height:14px;"></i> <?php echo htmlspecialchars($b['whatsapp']); ?>
                                    </a>
                                </td>
                                <td class="text-muted" style="font-size:0.9rem;">
                                    <?php echo date('d M Y', strtotime($b['created_at'])); ?>
                                </td>
                                <td>
                                    <?php if($admin_names): ?>
                                        <span style="font-size:0.9rem;"><?php echo htmlspecialchars($admin_names); ?></span>
                                    <?php else: ?>
                                        <span style="font-size:0.8rem; color:var(--danger-color); font-style:italic;">Sin Asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                <div class="action-grid justify-end">
                                    <a href="<?php echo $b['slug']; ?>" target="_blank" class="btn btn-icon btn-blue" style="background:var(--secondary-color);" title="Ver Catálogo">
                                        <i data-feather="eye"></i>
                                    </a>
                                    <a href="index.php?view=stats_business&id=<?php echo $b['id']; ?>" class="btn btn-icon btn-blue" style="background:#0ea5e9;" title="Ver Estadísticas">
                                        <i data-feather="bar-chart-2"></i>
                                    </a>
                                        <a href="#" onclick="openEditModal(<?php echo $b['id']; ?>, '<?php echo htmlspecialchars($b['name'], ENT_QUOTES); ?>', '<?php echo $b['status']; ?>', '<?php echo $b['plan']; ?>', '<?php echo $b['currency'] ?? 'PEN'; ?>', '<?php echo $b['subscription_end']; ?>', '<?php echo htmlspecialchars(str_replace(["\r", "\n"], ['\r', '\n'], $b['internal_notes'] ?? ''), ENT_QUOTES); ?>')" class="btn btn-icon btn-blue" title="Editar">
                                            <i data-feather="edit-2"></i>
                                        </a>
                                        <a href="#" onclick="openAssignModal(<?php echo $b['id']; ?>, '<?php echo htmlspecialchars($b['name']); ?>')" class="btn btn-icon btn-green" title="Asignar Admin">
                                            <i data-feather="user-plus"></i>
                                        </a>
                                        <a href="actions/business_delete.php?id=<?php echo $b['id']; ?>" class="btn btn-icon btn-red" onclick="return confirm('¿Eliminar negocio y todos sus datos?')" title="Eliminar">
                                            <i data-feather="trash-2"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Businesses -->
            <?php if ($total_pages_biz > 1): ?>
            <div class="pagination">
                <?php if ($page_biz > 1): ?>
                    <a href="?view=dashboard_super&page_biz=<?php echo $page_biz - 1; ?>&page_users=<?php echo $page_users; ?>#businesses" class="page-link"><i data-feather="chevron-left" style="width:14px;height:14px;"></i></a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages_biz; $i++): ?>
                    <a href="?view=dashboard_super&page_biz=<?php echo $i; ?>&page_users=<?php echo $page_users; ?>#businesses" class="page-link <?php echo $i === $page_biz ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page_biz < $total_pages_biz): ?>
                    <a href="?view=dashboard_super&page_biz=<?php echo $page_biz + 1; ?>&page_users=<?php echo $page_users; ?>#businesses" class="page-link"><i data-feather="chevron-right" style="width:14px;height:14px;"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="card" id="users">
            <h2 class="mb-4">Gestión de Usuarios</h2>
            <div style="overflow-x:auto;">
                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Negocio Asignado</th>
                            <th>Registro</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex items-center" style="gap:10px;">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                        </div>
                                        <span style="font-weight:500;"><?php echo htmlspecialchars($u['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php if($u['role_name'] === 'super_admin'): ?>
                                        <span class="badge badge-info">Super Admin</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:var(--bg-body); color:var(--text-muted);">Admin Negocio</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($u['business_name']): ?>
                                        <a href="<?php echo htmlspecialchars($u['business_slug'] ?? $u['business_name']); // Assuming slug might not be available in this query, checking below ?>" target="_blank" style="text-decoration:none; color:var(--primary-color); font-weight:500;">
                                            <?php echo htmlspecialchars($u['business_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                <td class="text-right">
                                    <a href="actions/user_delete.php?id=<?php echo $u['id']; ?>" onclick="return confirm('¿Eliminar usuario?')" class="btn btn-icon btn-red" title="Eliminar Usuario">
                                        <i data-feather="trash-2"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Users -->
            <?php if ($total_pages_users > 1): ?>
            <div class="pagination">
                <?php if ($page_users > 1): ?>
                    <a href="?page_users=<?php echo $page_users - 1; ?>&page_biz=<?php echo $page_biz; ?>#users" class="page-link"><i data-feather="chevron-left" style="width:14px;height:14px;"></i></a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages_users; $i++): ?>
                    <a href="?page_users=<?php echo $i; ?>&page_biz=<?php echo $page_biz; ?>#users" class="page-link <?php echo $i === $page_users ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page_users < $total_pages_users): ?>
                    <a href="?page_users=<?php echo $page_users + 1; ?>&page_biz=<?php echo $page_biz; ?>#users" class="page-link"><i data-feather="chevron-right" style="width:14px;height:14px;"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Create Business -->
    <div id="modal-create" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modal-create').style.display='none'">&times;</span>
            <h2 class="modal-header-title">Crear Nuevo Negocio</h2>
            <form action="actions/business_create.php" method="POST" enctype="multipart/form-data">
                <label>Nombre del Negocio</label>
                <input type="text" name="name" placeholder="Ej. Tienda de Ropa" required>
                
                <label>WhatsApp</label>
                <input type="text" name="whatsapp" placeholder="Ej. 51999999999">
                
                <div class="form-grid">
                    <div>
                        <label>Plan</label>
                        <select name="plan">
                            <option value="basico">Básico (S/ 150)</option>
                            <option value="pro">Pro (S/ 270)</option>
                            <option value="premium">Premium (S/ 400)</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <div>
                        <label>Moneda</label>
                        <select name="currency">
                            <option value="PEN" selected>PEN (S/)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="MXN">MXN ($)</option>
                            <option value="COP">COP ($)</option>
                        </select>
                    </div>
                </div>

                <label>Fecha Vencimiento (Opcional)</label>
                <input type="date" name="subscription_end">
                
                <label>Logo</label>
                <input type="file" name="logo">
                
                <button type="submit" class="btn btn-green btn-block">
                    <i data-feather="save"></i> Guardar Negocio
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Assign Admin -->
    <div id="modal-assign" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modal-assign').style.display='none'">&times;</span>
            <h2 class="modal-header-title">Asignar Administrador</h2>
            <p class="assign-text">Asignando a: <strong id="assign-business-name" class="assign-business-name"></strong></p>
            
            <form action="actions/admin_create.php" method="POST">
                <input type="hidden" name="business_id" id="assign-business-id">
                
                <label>Nombre Completo</label>
                <input type="text" name="name" required placeholder="Nombre del admin">
                
                <label>Correo Electrónico</label>
                <input type="email" name="email" required placeholder="correo@ejemplo.com">
                
                <label>Contraseña</label>
                <input type="password" name="password" required placeholder="******">
                
                <button type="submit" class="btn btn-green btn-block">
                    <i data-feather="user-plus"></i> Crear Usuario
                </button>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Business (Structure defined below) -->


    <!-- Modal Edit Business -->
    <div id="modal-edit" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modal-edit').style.display='none'">&times;</span>
            <h2 class="modal-header-title">Editar Negocio</h2>
            <form action="actions/business_update.php" method="POST">
                <input type="hidden" name="id" id="edit-id">
                
                <label>Nombre</label>
                <input type="text" name="name" id="edit-name" required>
                
                <div class="form-grid">
                    <div>
                        <label>Estado</label>
                        <select name="status" id="edit-status">
                            <option value="active">Activo</option>
                            <option value="suspended">Suspendido</option>
                        </select>
                    </div>
                    <div>
                        <label>Plan</label>
                        <select name="plan" id="edit-plan">
                            <option value="basico">Básico</option>
                            <option value="pro">Pro</option>
                            <option value="premium">Premium</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div>
                         <label>Moneda</label>
                         <select name="currency" id="edit-currency">
                            <option value="PEN">PEN</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="MXN">MXN</option>
                            <option value="COP">COP</option>
                        </select>
                    </div>
                    <div>
                        <label>Vencimiento</label>
                        <input type="date" name="subscription_end" id="edit-subscription_end">
                    </div>
                </div>

                <label>Notas Internas</label>
                <textarea name="internal_notes" id="edit-internal_notes" rows="3"></textarea>
                
                <button type="submit" class="btn btn-blue btn-block">
                    <i data-feather="save"></i> Guardar Cambios
                </button>
            </form>
        </div>
    </div>
    
    <!-- Modal Inactive List -->
    <div id="modal-inactive" class="modal">
        <div class="modal-content modal-inactive-content">
            <span class="close" onclick="document.getElementById('modal-inactive').style.display='none'">&times;</span>
            <h2 class="modal-header-title">Negocios Inactivos (30 días)</h2>
            <p class="inactive-description">Estos negocios no han recibido clics en WhatsApp en el último mes.</p>
            
            <div class="inactive-table-container">
                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th>Negocio</th>
                            <th>Última Actividad</th>
                            <th>WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inactive_businesses_alert as $ib): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ib['name']); ?></td>
                                <td class="last-activity-date">
                                    <?php echo $ib['last_click'] ? date('d/m/Y', strtotime($ib['last_click'])) : 'Nunca'; ?>
                                </td>
                                <td>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $ib['whatsapp']); ?>" target="_blank" class="btn btn-sm btn-green contact-btn">
                                        <i data-feather="message-circle" class="contact-icon"></i> Contactar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($inactive_businesses_alert)): ?>
                            <tr><td colspan="3" class="empty-inactive-msg">¡Todo excelente! Todos los negocios están activos.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        feather.replace();

        // Modal Logic
        function openEditModal(id, name, status, plan, currency, subEnd, notes) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-status').value = status;
            document.getElementById('edit-plan').value = plan;
            document.getElementById('edit-currency').value = currency;
            document.getElementById('edit-subscription_end').value = subEnd ? subEnd.split(' ')[0] : '';
            document.getElementById('edit-internal_notes').value = notes;
            document.getElementById('modal-edit').style.display = 'block';
        }

        function openAssignModal(id, name) {
            document.getElementById('assign-business-id').value = id;
            document.getElementById('assign-business-name').textContent = name;
            document.getElementById('modal-assign').style.display = 'block';
        }

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>
