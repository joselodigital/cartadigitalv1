<div class="sidebar">
    <div class="sidebar-header">
        <h3>Admin Negocio</h3>
        <p><?php echo isset($business['name']) ? htmlspecialchars($business['name']) : 'Mi Negocio'; ?></p>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Negocio</div>
        <a href="index.php?view=dashboard_business" class="<?php echo (!isset($_GET['view']) || $_GET['view']=='dashboard_business')?'active':''; ?>">
            <i class="icon">📊</i> Resumen
        </a>
        <a href="index.php?view=dashboard_business#products">
            <i class="icon">📦</i> Productos
        </a>
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] !== 'colaborador'): ?>
            <a href="index.php?view=dashboard_business#config">
                <i class="icon">⚙️</i> Configuración
            </a>
            <a href="index.php?view=dashboard_business#collabs">
                <i class="icon">👥</i> Colaboradores
            </a>
        <?php endif; ?>
        
        <div class="nav-section">Externo</div>
        <a href="<?php echo isset($business['slug']) ? $business['slug'] : '#'; ?>" target="_blank">
            <i class="icon">🔗</i> Ver Mi Catálogo
        </a>
        
        <a href="actions/logout.php" class="logout">
            <i class="icon">🚪</i> Cerrar Sesión
        </a>
    </nav>
</div>