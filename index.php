<?php
// index.php
require_once 'config/db.php';
require_once 'includes/functions.php';

$view = isset($_GET['view']) ? $_GET['view'] : 'home';

switch ($view) {
    case 'login':
        if (isLoggedIn()) {
             // Redirect based on role if already logged in
             if (hasRole('super_admin')) redirect('index.php?view=dashboard_super');
             if (hasRole('admin_negocio')) redirect('index.php?view=dashboard_business');
             if (hasRole('colaborador')) redirect('index.php?view=dashboard_collab');
        }
        include 'views/login.php';
        break;

    case 'dashboard_super':
        requireRole('super_admin');
        include 'views/dashboard_super.php';
        break;

    case 'stats_business':
        requireRole('super_admin');
        include 'views/stats_business.php';
        break;

    case 'users_list':
        requireRole('super_admin');
        include 'views/users_list.php';
        break;

    case 'audit_log':
        requireRole('super_admin');
        include 'views/audit_log.php';
        break;

    case 'tickets':
        requireRole('super_admin');
        include 'views/tickets.php';
        break;

    case 'plans_list':
        requireRole('super_admin');
        include 'views/plans_list.php';
        break;

    case 'whatsapp_settings':
        requireRole('super_admin');
        include 'views/whatsapp_settings.php';
        break;
    
    case 'system_settings':
        requireRole('super_admin');
        include 'views/system_settings.php';
        break;
    
    case 'catalogs_control':
        requireRole('super_admin');
        include 'views/catalogs_control.php';
        break;

    case 'search_results':
        requireRole('super_admin');
        include 'views/search_results.php';
        break;

    case 'dashboard_business':
        requireRole('admin_negocio');
        include 'views/dashboard_business.php';
        break;
        
    case 'dashboard_collab':
        requireRole('colaborador');
        include 'views/dashboard_collab.php';
        break;

    case 'catalog':
        // Public access
        include 'views/catalog.php';
        break;

    default:
        // Home page or redirect to login
        if (isLoggedIn()) {
             if (hasRole('super_admin')) redirect('index.php?view=dashboard_super');
             if (hasRole('admin_negocio')) redirect('index.php?view=dashboard_business');
             if (hasRole('colaborador')) redirect('index.php?view=dashboard_collab');
        } else {
            redirect('index.php?view=login');
        }
        break;
}
?>
