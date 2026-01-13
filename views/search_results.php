<?php
// views/search_results.php
requireRole('super_admin');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results_biz = [];
$results_users = [];

if ($query) {
    // Search Businesses
    $stmt = $pdo->prepare("SELECT * FROM businesses WHERE name LIKE ? OR slug LIKE ? ORDER BY created_at DESC");
    $term = "%$query%";
    $stmt->execute([$term, $term]);
    $results_biz = $stmt->fetchAll();

    // Search Users
    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name, b.name as business_name 
                           FROM users u 
                           LEFT JOIN roles r ON u.role_id = r.id 
                           LEFT JOIN businesses b ON u.business_id = b.id 
                           WHERE u.name LIKE ? OR u.email LIKE ? OR b.name LIKE ?
                           ORDER BY u.created_at DESC");
    $stmt->execute([$term, $term, $term]);
    $results_users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados de Búsqueda: <?php echo htmlspecialchars($query); ?></title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_super.php'; ?>

    <div class="content">
        <?php include 'views/partials/topbar_super.php'; ?>
        <h1>Resultados para: "<?php echo htmlspecialchars($query); ?>"</h1>

        <div class="card">
            <h2>Negocios (<?php echo count($results_biz); ?>)</h2>
            <?php if (count($results_biz) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results_biz as $b): ?>
                            <tr>
                                <td><?php echo $b['id']; ?></td>
                                <td><?php echo htmlspecialchars($b['name']); ?></td>
                                <td>
                                    <?php 
                                        $planClass = 'badge-neutral';
                                        switch($b['plan']) {
                                            case 'premium': $planClass = 'badge-warning'; break;
                                            case 'pro': $planClass = 'badge-info'; break;
                                            case 'enterprise': $planClass = 'badge-info'; break;
                                            default: $planClass = 'badge-neutral';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $planClass; ?>">
                                        <?php echo strtoupper($b['plan'] ? $b['plan'] : 'BASICO'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $statusClass = 'badge-danger';
                                        $statusLabel = 'Inactivo';
                                        if($b['status'] === 'active') {
                                            $statusClass = 'badge-success';
                                            $statusLabel = 'Activo';
                                        } elseif($b['status'] === 'suspended') {
                                            $statusClass = 'badge-warning';
                                            $statusLabel = 'Suspendido';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusLabel; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($b['slug']); ?>" target="_blank" class="btn btn-blue">Ver Catálogo</a>
                                    <!-- Add simple redirect to dashboard edit? No, just info for now -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No se encontraron negocios.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Usuarios (<?php echo count($results_users); ?>)</h2>
            <?php if (count($results_users) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Negocio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results_users as $u): ?>
                            <tr>
                                <td>
                                    <div class="font-bold"><?php echo htmlspecialchars($u['name']); ?></div>
                                    <div class="text-sm text-muted"><?php echo htmlspecialchars($u['email']); ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $roleName = $u['role_name'] ?? 'Sin Rol';
                                        $roleClass = 'badge-neutral';
                                        
                                        if (stripos($roleName, 'super') !== false) {
                                            $roleClass = 'badge-danger';
                                        } elseif (stripos($roleName, 'admin') !== false) {
                                            $roleClass = 'badge-info';
                                        } elseif (stripos($roleName, 'collab') !== false || stripos($roleName, 'personal') !== false) {
                                            $roleClass = 'badge-success';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $roleClass; ?>">
                                        <?php echo htmlspecialchars($roleName); ?>
                                    </span>
                                </td>
                                <td><?php echo $u['business_name'] ? htmlspecialchars($u['business_name']) : '<span class="text-muted">-</span>'; ?></td>
                                <td>
                                    <a href="actions/user_delete.php?id=<?php echo $u['id']; ?>" class="btn btn-red btn-sm" onclick="return confirm('¿Eliminar usuario?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No se encontraron usuarios.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
