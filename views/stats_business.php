<?php
// views/stats_business.php
requireRole('super_admin');

$business_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$business_id) {
    die("ID de negocio inválido.");
}

// Fetch Business Info
$stmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
$stmt->execute([$business_id]);
$business = $stmt->fetch();

if (!$business) {
    die("Negocio no encontrado.");
}

// Stats for this business
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE business_id = ?");
$stmt->execute([$business_id]);
$total_products = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_clicks WHERE business_id = ?");
$stmt->execute([$business_id]);
$total_clicks = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_clicks WHERE business_id = ? AND MONTH(clicked_at) = MONTH(CURRENT_DATE()) AND YEAR(clicked_at) = YEAR(CURRENT_DATE())");
$stmt->execute([$business_id]);
$month_clicks = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_clicks WHERE business_id = ? AND DATE(clicked_at) = CURDATE()");
$stmt->execute([$business_id]);
$today_clicks = $stmt->fetch()['total'];

// Get monthly history for chart (last 6 months)
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(clicked_at, '%Y-%m') as month, 
        COUNT(*) as count 
    FROM whatsapp_clicks 
    WHERE business_id = ? 
    GROUP BY month 
    ORDER BY month DESC 
    LIMIT 6
");
$stmt->execute([$business_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas - <?php echo htmlspecialchars($business['name']); ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            color: white;
        }
        .stat-card h3 { font-size: 2.5em; margin: 0; }
        .stat-card p { margin: 5px 0 0; opacity: 0.9; }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .history-table th, .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .history-table th { background: #f8f9fa; }
    </style>
</head>
<body class="admin-body">
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Super Admin</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php?view=dashboard_super"><i class="icon">🔙</i> Volver al Panel</a>
            <a href="actions/logout.php" class="logout"><i class="icon">🚪</i> Cerrar Sesión</a>
        </nav>
    </div>

    <div class="content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h1>Estadísticas: <?php echo htmlspecialchars($business['name']); ?></h1>
            <a href="index.php?view=dashboard_super" class="btn btn-blue">Volver</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><?php echo $total_products; ?></h3>
                <p>Productos Activos</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);">
                <h3><?php echo $today_clicks; ?></h3>
                <p>Pedidos Hoy 💬</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><?php echo $month_clicks; ?></h3>
                <p>Pedidos Mes Actual</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); color:#333;">
                <h3><?php echo $total_clicks; ?></h3>
                <p>Total Histórico</p>
            </div>
        </div>

        <div class="card">
            <h2>Historial de Pedidos (Últimos 6 Meses)</h2>
            <?php if(count($history) > 0): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Pedidos (Clics WhatsApp)</th>
                            <th>Barra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $max_val = 0;
                        foreach($history as $h) { if($h['count'] > $max_val) $max_val = $h['count']; }
                        if($max_val == 0) $max_val = 1;
                        ?>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?php echo $row['month']; ?></td>
                                <td><?php echo $row['count']; ?></td>
                                <td style="width: 60%;">
                                    <div style="background-color: #25D366; height: 20px; border-radius: 4px; width: <?php echo ($row['count'] / $max_val) * 100; ?>%;"></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="padding:20px; text-align:center; color:#666;">No hay datos históricos aún.</p>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        feather.replace();
    </script>
</body>
</html>
