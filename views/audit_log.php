<?php
// views/audit_log.php
requireRole('super_admin');

$filter_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Build Query
$query = "
    SELECT a.*, u.name as user_name, u.email as user_email
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
";

$params = [];
if ($filter_user_id) {
    $query .= " WHERE a.user_id = ?";
    $params[] = $filter_user_id;
}

$query .= " ORDER BY a.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get user info if filtering
$filter_user_name = '';
if ($filter_user_id) {
    $stmt_u = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt_u->execute([$filter_user_id]);
    $filter_user_name = $stmt_u->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Acciones</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_super.php'; ?>

    <div class="content">
        <?php include 'views/partials/topbar_super.php'; ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>
                Historial de Acciones 
                <?php if($filter_user_name) echo " - Usuario: " . htmlspecialchars($filter_user_name); ?>
            </h1>
            <a href="index.php?view=users_list" class="btn btn-blue">Volver a Usuarios</a>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Detalles</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <?php if($log['user_name']): ?>
                                        <strong><?php echo htmlspecialchars($log['user_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($log['user_email']); ?></small>
                                    <?php else: ?>
                                        <span style="color:gray;">Usuario Eliminado (ID: <?php echo $log['user_id']; ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $action_colors = [
                                        'login' => 'green',
                                        'login_failed' => 'red',
                                        'block_user' => 'red',
                                        'unblock_user' => 'blue',
                                        'create_business' => 'purple',
                                        'create_user' => 'purple',
                                        'delete_user' => 'red',
                                        'delete_business' => 'red',
                                        'delete_product' => 'red'
                                    ];
                                    $color = isset($action_colors[$log['action']]) ? $action_colors[$log['action']] : 'black';
                                    ?>
                                    <span style="color:<?php echo $color; ?>; font-weight:bold;">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No hay registros recientes.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();
    </script>
</body>
</html>
