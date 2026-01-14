<?php
requireRole('super_admin');

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            business_id INT NOT NULL,
            user_id INT NOT NULL,
            subject VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('open','closed') NOT NULL DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status_created (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {}

$filter_status = isset($_GET['status']) ? $_GET['status'] : 'open';
$query = "SELECT t.*, b.name as business_name, u.name as user_name 
          FROM tickets t 
          JOIN businesses b ON b.id = t.business_id 
          JOIN users u ON u.id = t.user_id ";
$params = [];
if ($filter_status === 'closed') {
    $query .= "WHERE t.status = 'closed' ";
} else {
    $query .= "WHERE t.status = 'open' ";
}
$query .= "ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tickets</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_super.php'; ?>

    <div class="content">
        <?php include 'views/partials/topbar_super.php'; ?>
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>Tickets</h1>
            <div>
                <a href="index.php?view=tickets&status=open" class="btn btn-blue" style="<?php echo $filter_status==='open'?'opacity:1':'opacity:0.6'; ?>">Abiertos</a>
                <a href="index.php?view=tickets&status=closed" class="btn btn-blue" style="<?php echo $filter_status==='closed'?'opacity:1':'opacity:0.6'; ?>">Cerrados</a>
            </div>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Negocio</th>
                        <th>Usuario</th>
                        <th>Asunto</th>
                        <th>Mensaje</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><?php echo $t['id']; ?></td>
                            <td><?php echo htmlspecialchars($t['business_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['subject']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($t['message'])); ?></td>
                            <td><?php echo $t['status']==='open' ? '<span style="color:#e67e22;font-weight:600">Abierto</span>' : '<span style="color:#2ecc71;font-weight:600">Cerrado</span>'; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></td>
                            <td>
                                <?php if($t['status'] === 'open'): ?>
                                    <a href="actions/ticket_status.php?id=<?php echo $t['id']; ?>&status=closed" class="btn btn-green">Cerrar</a>
                                <?php else: ?>
                                    <a href="actions/ticket_status.php?id=<?php echo $t['id']; ?>&status=open" class="btn btn-blue">Reabrir</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
