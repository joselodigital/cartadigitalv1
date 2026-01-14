<div class="sidebar">
    <div class="sidebar-header">
        <h3>Super Admin</h3>
        <p>Panel de Control</p>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <a href="index.php?view=dashboard_super" class="<?php echo (!isset($_GET['view']) || $_GET['view']=='dashboard_super')?'active':''; ?>">
            <i class="icon">🏠</i> Dashboard
        </a>
        <a href="index.php?view=tickets" class="<?php echo (isset($_GET['view']) && $_GET['view']=='tickets')?'active':''; ?>">
            <i class="icon">🎫</i> Tickets
        </a>

        <div class="nav-section">Gestión</div>
        <a href="index.php?view=users_list" class="<?php echo (isset($_GET['view']) && $_GET['view']=='users_list')?'active':''; ?>">
            <i class="icon">👥</i> Usuarios
        </a>
        <a href="index.php?view=plans_list" class="<?php echo (isset($_GET['view']) && $_GET['view']=='plans_list')?'active':''; ?>">
            <i class="icon">📦</i> Planes
        </a>
        <a href="index.php?view=catalogs_control" class="<?php echo (isset($_GET['view']) && $_GET['view']=='catalogs_control')?'active':''; ?>">
            <i class="icon">📊</i> Control de Catálogos
        </a>

        <div class="nav-section">Configuración</div>
        <a href="index.php?view=whatsapp_settings" class="<?php echo (isset($_GET['view']) && $_GET['view']=='whatsapp_settings')?'active':''; ?>">
            <i class="icon">💬</i> WhatsApp Inteligente
        </a>
        <a href="index.php?view=system_settings" class="<?php echo (isset($_GET['view']) && $_GET['view']=='system_settings')?'active':''; ?>">
            <i class="icon">⚙️</i> Ajustes del Sistema
        </a>
        <a href="index.php?view=audit_log" class="<?php echo (isset($_GET['view']) && $_GET['view']=='audit_log')?'active':''; ?>">
            <i class="icon">📜</i> Historial
        </a>

        <a href="actions/logout.php" class="logout">
            <i class="icon">🚪</i> Cerrar Sesión
        </a>
    </nav>
</div>
<div class="sidebar-overlay"></div>