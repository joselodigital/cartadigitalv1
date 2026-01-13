<div class="sidebar">
    <div class="sidebar-header">
        <h3>Colaborador</h3>
        <p><?php echo isset($business['name']) ? htmlspecialchars($business['name']) : 'Negocio'; ?></p>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Menú</div>
        <a href="index.php?view=dashboard_collab" class="<?php echo (!isset($_GET['view']) || $_GET['view']=='dashboard_collab')?'active':''; ?>">
            <i class="icon">🏠</i> Inicio
        </a>
        <a href="index.php?view=dashboard_collab#products">
            <i class="icon">📦</i> Productos
        </a>
        <a href="<?php echo $business['slug']; ?>" target="_blank">
            <i class="icon">🌐</i> Ver Catálogo
        </a>
        <a href="actions/logout.php" class="logout">
            <i class="icon">🚪</i> Cerrar Sesión
        </a>
    </nav>
</div>