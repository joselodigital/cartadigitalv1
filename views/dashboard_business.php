<?php
// views/dashboard_business.php
requireRole('admin_negocio');

$business_id = $_SESSION['business_id'];

// Fetch Business Info
$stmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
$stmt->execute([$business_id]);
$business = $stmt->fetch();

// Fetch Products with Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total products for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE business_id = ?");
$stmt->execute([$business_id]);
$total_products_pagination = $stmt->fetch()['total'];
$total_pages = ceil($total_products_pagination / $limit);

$stmt = $pdo->prepare("SELECT * FROM products WHERE business_id = :business_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':business_id', $business_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch Collaborators
$stmt = $pdo->prepare("SELECT * FROM users WHERE business_id = ? AND role_id = 3");
$stmt->execute([$business_id]);
$collaborators = $stmt->fetchAll();

// Stats for this business
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE business_id = ?");
$stmt->execute([$business_id]);
$total_products = $stmt->fetch()['total'];

// Fetch Plan Limit
$stmt = $pdo->prepare("SELECT product_limit FROM plans WHERE slug = ?");
$stmt->execute([$business['plan']]);
$plan_limit = $stmt->fetchColumn();
if ($plan_limit === false) $plan_limit = 15; // Fallback default
$limit_reached = ($total_products >= $plan_limit);

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_clicks WHERE business_id = ?");
$stmt->execute([$business_id]);
$total_clicks = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_clicks WHERE business_id = ? AND MONTH(clicked_at) = MONTH(CURRENT_DATE()) AND YEAR(clicked_at) = YEAR(CURRENT_DATE())");
$stmt->execute([$business_id]);
$month_clicks = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_clicks WHERE business_id = ? AND DATE(clicked_at) = CURDATE()");
$stmt->execute([$business_id]);
$today_clicks = $stmt->fetch()['total'];

// Fetch Payment Methods
$stmt = $pdo->prepare("SELECT * FROM business_payment_methods WHERE business_id = ? ORDER BY created_at DESC");
$stmt->execute([$business_id]);
$payment_methods = $stmt->fetchAll();

// Currency Mapping
$currency_code = $business['currency'] ?? 'PEN';
$currency_symbols = [
    'PEN' => 'S/', 'USD' => '$', 'EUR' => '€', 'MXN' => '$', 
    'COP' => '$', 'ARS' => '$', 'CLP' => '$', 'BOB' => 'Bs.', 
    'BRL' => 'R$', 'GTQ' => 'Q'
];
$currency_symbol = $currency_symbols[$currency_code] ?? '$';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Negocio - <?php echo htmlspecialchars($business['name']); ?></title>
    <link rel="stylesheet" href="public/css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <style>
        /* Specific styles for business dashboard if any not covered by style.css */
    </style>
</head>
<body class="admin-body">
    <?php include 'views/partials/sidebar_business.php'; ?>
    <div class="sidebar-overlay"></div>

    <div class="content">
        <div class="section-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <button id="sidebar-toggle" class="sidebar-toggle">
                    <i data-feather="menu"></i>
                </button>
                <h1>Panel de Administración</h1>
            </div>
            <div class="user-profile">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($business['name']); ?></div>
                    <div class="user-role">Administrador</div>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($business['name'], 0, 1)); ?>
                </div>
            </div>
        </div>

        <div class="tab-menu">
            <div class="tab-link active" onclick="switchTab('general')">Vista General</div>
            <div class="tab-link" onclick="switchTab('products')">Productos / Servicios</div>
            <?php if($_SESSION['role'] !== 'colaborador'): ?>
                <div class="tab-link" onclick="switchTab('config')">Configuración</div>
                <div class="tab-link" onclick="switchTab('collabs')">Colaboradores</div>
            <?php endif; ?>
        </div>

        <!-- TAB: GENERAL -->
        <div id="tab-general" class="tab-content">
            <div class="stats">
                <div class="stat-box" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Productos Activos</p>
                    <i data-feather="package" style="position:absolute; right:20px; bottom:20px; opacity:0.2; width:48px; height:48px;"></i>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <h3><?php echo $today_clicks; ?></h3>
                    <p>Pedidos Hoy</p>
                    <i data-feather="message-circle" style="position:absolute; right:20px; bottom:20px; opacity:0.2; width:48px; height:48px;"></i>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <h3><?php echo $month_clicks; ?></h3>
                    <p>Pedidos Mes Actual</p>
                    <i data-feather="calendar" style="position:absolute; right:20px; bottom:20px; opacity:0.2; width:48px; height:48px;"></i>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
                    <h3><?php echo $total_clicks; ?></h3>
                    <p>Total Histórico</p>
                    <i data-feather="trending-up" style="position:absolute; right:20px; bottom:20px; opacity:0.2; width:48px; height:48px;"></i>
                </div>
            </div>

            <div class="card">
                <h2>Estado del Negocio</h2>
                <p class="text-muted">Tu plan actual es: <strong><?php echo ucfirst($business['plan']); ?></strong></p>
                <p class="text-muted">Enlace a tu catálogo: <a href="<?php echo $business['slug']; ?>" target="_blank" style="color:var(--primary-color);">Ver Catálogo</a></p>
            </div>
        </div>

        <!-- TAB: PRODUCTS -->
        <div id="tab-products" class="tab-content" style="display:none;">
            <div class="section-header">
                <h2>Inventario <span style="font-size:0.6em; color:var(--text-muted); font-weight:normal;">(<?php echo $total_products; ?> / <?php echo $plan_limit > 9999 ? '∞' : $plan_limit; ?>)</span></h2>
                <?php if ($limit_reached): ?>
                    <button onclick="alert('Has alcanzado el límite de productos de tu plan (<?php echo $plan_limit; ?>). Contacta a soporte para mejorar tu plan.')" class="btn btn-secondary" style="opacity: 0.7; cursor: not-allowed; background-color: #6c757d; color: white;">
                        <i data-feather="lock"></i> Límite Alcanzado
                    </button>
                <?php else: ?>
                    <button onclick="document.getElementById('modal-product').style.display='block'" class="btn btn-green">
                        <i data-feather="plus"></i> Nuevo Producto
                    </button>
                <?php endif; ?>
            </div>

            <div style="background:#fff; border-radius:8px; border:1px solid #eee; padding:20px;">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $p): ?>
                        <div class="product-card-item">
                            <?php if($p['image']): ?>
                                <img src="public/uploads/<?php echo $p['image']; ?>" class="product-img">
                            <?php else: ?>
                                <div class="product-img" style="display:flex; align-items:center; justify-content:center; color:#ccc;">
                                    <i data-feather="image"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-info">
                                <h4 style="margin:0; font-size:1rem;"><?php echo htmlspecialchars($p['name']); ?></h4>
                                <div style="font-size:0.85rem; color:#64748b; margin-top:4px;">
                                    <span style="font-weight:600; color:var(--text-main);"><?php echo $currency_symbol ?? '$'; ?><?php echo number_format($p['price'], 2); ?></span>
                                    <span style="margin:0 8px;">•</span>
                                    <span>Stock: <?php echo isset($p['stock']) ? intval($p['stock']) : 0; ?></span>
                                </div>
                            </div>

                            <div class="product-actions">
                                <button onclick="openEditProduct(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['description'], ENT_QUOTES); ?>', '<?php echo $p['price']; ?>', '<?php echo isset($p['stock']) ? $p['stock'] : 0; ?>')" class="action-btn edit-btn" title="Editar">
                                    <i data-feather="edit-2" style="width:16px; height:16px;"></i>
                                </button>
                                <a href="actions/product_delete.php?id=<?php echo $p['id']; ?>" onclick="return confirm('¿Eliminar este producto?')" class="action-btn delete-btn" title="Eliminar">
                                    <i data-feather="trash-2" style="width:16px; height:16px;"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination-container" style="display:flex; justify-content:center; align-items:center; gap:10px; margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>#products" class="btn btn-sm btn-secondary" style="display:flex; align-items:center; gap:5px;">
                                <i data-feather="chevron-left" style="width:16px;"></i> Anterior
                            </a>
                        <?php endif; ?>
                        
                        <span style="font-weight:600; color:var(--text-secondary); font-size:0.9rem;">
                            Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                        </span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>#products" class="btn btn-sm btn-secondary" style="display:flex; align-items:center; gap:5px;">
                                Siguiente <i data-feather="chevron-right" style="width:16px;"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:40px; color:#64748b;">
                        <i data-feather="package" style="width:48px; height:48px; opacity:0.5; margin-bottom:10px;"></i>
                        <p>No tienes productos registrados aún.</p>
                        <?php if ($limit_reached): ?>
                            <button onclick="alert('Tu plan actual no permite agregar productos.')" class="btn btn-secondary btn-sm" style="margin-top:10px; opacity: 0.7; cursor: not-allowed; background-color: #6c757d; color: white;">Límite Alcanzado</button>
                        <?php else: ?>
                            <button onclick="document.getElementById('modal-product').style.display='block'" class="btn btn-blue btn-sm" style="margin-top:10px;">Crear el primero</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB: CONFIG -->
        <?php if($_SESSION['role'] !== 'colaborador'): ?>
        <div id="tab-config" class="tab-content" style="display:none;">
            <div class="card">
                <h2>Configuración General</h2>
                <form action="actions/business_update_self.php" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div>
                            <label>Nombre del Negocio:</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($business['name']); ?>" required>
                        </div>
                        <div>
                            <label>Tipo de Negocio:</label>
                            <select name="business_type">
                                <option value="product" <?php echo (!isset($business['business_type']) || $business['business_type'] == 'product') ? 'selected' : ''; ?>>Venta de Productos</option>
                                <option value="service" <?php echo (isset($business['business_type']) && $business['business_type'] == 'service') ? 'selected' : ''; ?>>Servicios</option>
                            </select>
                        </div>
                        <div style="grid-column: span 2;">
                            <label>Slogan / Subtítulo:</label>
                            <input type="text" name="slogan" value="<?php echo htmlspecialchars($business['slogan'] ?? 'Bienvenido a nuestra tienda oficial. Encuentra los mejores productos y atención personalizada.'); ?>" placeholder="Escribe aquí el texto de bienvenida...">
                        </div>
                        <div style="grid-column: span 2;">
                            <label>Foto de Portada (Hero):</label>
                            <?php if(!empty($business['cover_image'])): ?>
                                <div style="margin-bottom:10px;">
                                    <img src="public/uploads/<?php echo $business['cover_image']; ?>" style="max-height:100px; border-radius:8px; border:1px solid #ddd;">
                                    <p style="font-size:0.8rem; color:#666; margin-top:5px;">Imagen actual</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="cover_image" accept="image/*" class="form-control">
                            <p style="font-size:0.8rem; color:#64748b; margin-top:4px;">Recomendado: 1200x400px o superior.</p>
                        </div>
                        <div>
                            <label>Color Principal (Botones y Detalles):</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" name="color_primary" value="<?php echo !empty($business['color_primary']) ? $business['color_primary'] : '#2563eb'; ?>" style="width:50px; height:40px; border:1px solid #ddd; border-radius:4px; padding:0;">
                                <input type="text" value="<?php echo !empty($business['color_primary']) ? $business['color_primary'] : '#2563eb'; ?>" readonly style="width:100px; border:none; background:transparent; color:#666;">
                            </div>
                        </div>
                        <div>
                            <label>Color de Fondo (Página):</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" name="bg_color" value="<?php echo !empty($business['bg_color']) ? $business['bg_color'] : '#f8fafc'; ?>" style="width:50px; height:40px; border:1px solid #ddd; border-radius:4px; padding:0;">
                                <input type="text" value="<?php echo !empty($business['bg_color']) ? $business['bg_color'] : '#f8fafc'; ?>" readonly style="width:100px; border:none; background:transparent; color:#666;">
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div>
                            <label>Moneda:</label>
                            <select name="currency">
                                <option value="PEN" <?php echo ($business['currency'] ?? 'PEN') == 'PEN' ? 'selected' : ''; ?>>PEN - Sol Peruano (S/)</option>
                                <option value="USD" <?php echo ($business['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD - Dólar Estadounidense ($)</option>
                                <option value="EUR" <?php echo ($business['currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR - Euro (€)</option>
                                <option value="MXN" <?php echo ($business['currency'] ?? '') == 'MXN' ? 'selected' : ''; ?>>MXN - Peso Mexicano ($)</option>
                                <option value="COP" <?php echo ($business['currency'] ?? '') == 'COP' ? 'selected' : ''; ?>>COP - Peso Colombiano ($)</option>
                                <option value="ARS" <?php echo ($business['currency'] ?? '') == 'ARS' ? 'selected' : ''; ?>>ARS - Peso Argentino ($)</option>
                                <option value="CLP" <?php echo ($business['currency'] ?? '') == 'CLP' ? 'selected' : ''; ?>>CLP - Peso Chileno ($)</option>
                                <option value="BOB" <?php echo ($business['currency'] ?? '') == 'BOB' ? 'selected' : ''; ?>>BOB - Boliviano (Bs.)</option>
                                <option value="BRL" <?php echo ($business['currency'] ?? '') == 'BRL' ? 'selected' : ''; ?>>BRL - Real Brasileño (R$)</option>
                                <option value="GTQ" <?php echo ($business['currency'] ?? '') == 'GTQ' ? 'selected' : ''; ?>>GTQ - Quetzal (Q)</option>
                            </select>
                        </div>

                        <!-- Payment Methods Manager -->
                        <div style="grid-column: span 2; margin-top:20px;">
                            <h3 style="margin-bottom:15px; font-size:1.1rem; border-bottom:1px solid #eee; padding-bottom:10px;">Gestión de Métodos de Pago</h3>
                            
                            <!-- Add New Method -->
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:15px; margin-bottom:20px;">
                                <h4 style="margin:0 0 10px 0; font-size:0.95rem;">Agregar Nuevo Método</h4>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                                    <input type="text" id="new_method_name" placeholder="Nombre (Ej. Yape, BCP)" class="form-control-sm">
                                    <input type="file" id="new_method_img" accept="image/*" class="form-control-sm">
                                </div>
                                <textarea id="new_method_details" placeholder="Detalles de cuenta (Número, CCI, Titular...)" rows="2" style="width:100%; margin-top:10px;" class="form-control-sm"></textarea>
                                <button type="button" onclick="submitNewPaymentMethod()" class="btn btn-sm btn-blue" style="margin-top:10px;">
                                    <i data-feather="plus"></i> Agregar Método
                                </button>
                            </div>

                            <!-- List Existing Methods -->
                            <div class="payment-methods-list">
                                <?php if(count($payment_methods) > 0): ?>
                                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:15px;">
                                        <?php foreach($payment_methods as $pm): ?>
                                            <div style="background:white; border:1px solid #e2e8f0; border-radius:8px; padding:15px; position:relative;">
                                                <div style="display:flex; justify-content:space-between; align-items:start;">
                                                    <div style="display:flex; align-items:center; gap:10px;">
                                                        <?php if($pm['qr_image']): ?>
                                                            <img src="public/uploads/<?php echo $pm['qr_image']; ?>" style="width:40px; height:40px; object-fit:cover; border-radius:6px; border:1px solid #eee;">
                                                        <?php else: ?>
                                                            <div style="width:40px; height:40px; background:#f1f5f9; border-radius:6px; display:flex; align-items:center; justify-content:center;"><i data-feather="credit-card" style="width:20px; color:#94a3b8;"></i></div>
                                                        <?php endif; ?>
                                                        <strong style="font-size:0.95rem;"><?php echo htmlspecialchars($pm['method_name']); ?></strong>
                                                    </div>
                                                    <a href="actions/payment_method_delete.php?id=<?php echo $pm['id']; ?>" onclick="return confirm('¿Eliminar este método?')" style="color:#ef4444; padding:5px;"><i data-feather="trash-2" style="width:16px;"></i></a>
                                                </div>
                                                <div style="margin-top:10px; font-size:0.85rem; color:#64748b; white-space:pre-wrap;"><?php echo htmlspecialchars($pm['account_details']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="color:#64748b; font-style:italic;">No hay métodos de pago registrados.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="grid-column: span 2; border-top:1px solid #eee; margin:10px 0;"></div>

                        <div>
                            <label>Número de WhatsApp:</label>
                            <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($business['whatsapp']); ?>">
                        </div>
                        <div>
                            <label>Sitio Web:</label>
                            <input type="url" name="website" value="<?php echo htmlspecialchars($business['website'] ?? ''); ?>" placeholder="https://...">
                        </div>
                    </div>

                    <h3 style="margin:24px 0 16px 0; padding-bottom:8px; border-bottom:1px solid #eee;">Redes Sociales</h3>
                    <div class="form-grid">
                        <div>
                            <label>Facebook:</label>
                            <input type="url" name="facebook" value="<?php echo htmlspecialchars($business['facebook'] ?? ''); ?>" placeholder="https://facebook.com/...">
                        </div>
                        <div>
                            <label>Instagram:</label>
                            <input type="url" name="instagram" value="<?php echo htmlspecialchars($business['instagram'] ?? ''); ?>" placeholder="https://instagram.com/...">
                        </div>
                        <div>
                            <label>TikTok:</label>
                            <input type="url" name="tiktok" value="<?php echo htmlspecialchars($business['tiktok'] ?? ''); ?>" placeholder="https://tiktok.com/@...">
                        </div>
                        <div>
                            <label>Twitter/X:</label>
                            <input type="url" name="twitter" value="<?php echo htmlspecialchars($business['twitter'] ?? ''); ?>" placeholder="https://twitter.com/...">
                        </div>
                    </div>

                    <h3 style="margin:24px 0 16px 0; padding-bottom:8px; border-bottom:1px solid #eee;">Personalización "Sobre Nosotros"</h3>
                    <div class="form-grid">
                        <div style="grid-column: span 2;">
                            <label>Título de la Sección:</label>
                            <input type="text" name="about_title" value="<?php echo htmlspecialchars($business['about_title'] ?? 'Tu mejor opción en calidad y servicio'); ?>" placeholder="Ej. Tu mejor opción en calidad y servicio">
                        </div>
                        <div style="grid-column: span 2;">
                            <label>Descripción / Texto Principal:</label>
                            <textarea name="description" rows="4" placeholder="Describe tu negocio..."><?php echo htmlspecialchars($business['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <h3 style="margin:24px 0 16px 0; padding-bottom:8px; border-bottom:1px solid #eee;">Personalización Landing Page (Iconos)</h3>
                    <div class="form-section">
                        <h4 style="margin-top:0;">Bloque 1 (Icono: Bolsa)</h4>
                        <div class="form-grid">
                            <div>
                                <label>Título:</label>
                                <input type="text" name="feature1_title" value="<?php echo htmlspecialchars($business['feature1_title'] ?? 'Fácil de Comprar'); ?>">
                            </div>
                            <div>
                                <label>Descripción:</label>
                                <input type="text" name="feature1_desc" value="<?php echo htmlspecialchars($business['feature1_desc'] ?? 'Elige tus productos favoritos y pídelos directamente por WhatsApp.'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 style="margin-top:0;">Bloque 2 (Icono: Escudo)</h4>
                        <div class="form-grid">
                            <div>
                                <label>Título:</label>
                                <input type="text" name="feature2_title" value="<?php echo htmlspecialchars($business['feature2_title'] ?? 'Compra Segura'); ?>">
                            </div>
                            <div>
                                <label>Descripción:</label>
                                <input type="text" name="feature2_desc" value="<?php echo htmlspecialchars($business['feature2_desc'] ?? 'Tratas directamente con nosotros, sin intermediarios ni comisiones.'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4 style="margin-top:0;">Bloque 3 (Icono: Mensaje)</h4>
                        <div class="form-grid">
                            <div>
                                <label>Título:</label>
                                <input type="text" name="feature3_title" value="<?php echo htmlspecialchars($business['feature3_title'] ?? 'Atención Personal'); ?>">
                            </div>
                            <div>
                                <label>Descripción:</label>
                                <input type="text" name="feature3_desc" value="<?php echo htmlspecialchars($business['feature3_desc'] ?? 'Te atendemos personalmente para confirmar detalles de tu pedido.'); ?>">
                            </div>
                        </div>
                    </div>

                    <?php if ($can_be_private ?? false): ?>
                        <div style="background:#eff6ff; padding:20px; border-radius:8px; margin-top:20px; border:1px solid #bfdbfe;">
                            <h3 style="margin-top:0; color:#1e40af;">🔒 Privacidad del Catálogo</h3>
                            <label style="display:inline-flex; align-items:center; cursor:pointer;">
                                <input type="checkbox" name="is_private" value="1" <?php echo $business['is_private'] ? 'checked' : ''; ?> onclick="togglePasswordInput(this)">
                                <span style="margin-left:8px; font-weight:600;">Hacer Catálogo Privado</span>
                            </label>
                            
                            <div id="password-field" style="margin-top:15px; display: <?php echo $business['is_private'] ? 'block' : 'none'; ?>;">
                                <label>Contraseña de Acceso:</label>
                                <input type="text" name="password" value="<?php echo htmlspecialchars($business['password']); ?>" placeholder="Define una contraseña">
                            </div>
                        </div>
                        <script>
                            function togglePasswordInput(checkbox) {
                                document.getElementById('password-field').style.display = checkbox.checked ? 'block' : 'none';
                            }
                        </script>
                    <?php endif; ?>

                    <div style="margin-top:30px; text-align:right;">
                        <button type="submit" class="btn btn-blue">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB: COLLABORATORS -->
        <?php if($_SESSION['role'] !== 'colaborador'): ?>
        <div id="tab-collabs" class="tab-content" style="display:none;">
            <div class="section-header">
                <h2>Equipo de Trabajo</h2>
                <button onclick="document.getElementById('modal-collab').style.display='block'" class="btn btn-green">
                    <i data-feather="user-plus"></i> Nuevo Colaborador
                </button>
            </div>
            
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($collaborators as $c): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:32px; height:32px; background:#e2e8f0; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#64748b;">
                                            <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($c['email']); ?></td>
                                <td>
                                    <a href="actions/user_delete.php?id=<?php echo $c['id']; ?>" onclick="return confirm('¿Eliminar colaborador?')" class="btn btn-red btn-sm">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div style="margin-top:40px; padding-top:20px; border-top:1px solid #eee;">
             <div class="card" style="background:#fff7ed; border-color:#ffedd5;">
                <h3 style="margin-top:0; color:#c2410c;">¿Necesitas ayuda?</h3>
                <form action="actions/ticket_create.php" method="POST">
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="subject" placeholder="Asunto del problema" required style="margin:0;">
                        <button type="submit" class="btn btn-blue" style="background:#ea580c;">Enviar Ticket</button>
                    </div>
                    <textarea name="message" rows="2" placeholder="Describe tu problema..." required style="margin-top:10px;"></textarea>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Create Product -->
    <div id="modal-product" class="modal">
        <div class="modal-content">
            <span onclick="document.getElementById('modal-product').style.display='none'" style="float:right; cursor:pointer;">&times;</span>
            <h2>Nuevo Producto</h2>
            <form action="actions/product_create.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="name" placeholder="Nombre del Producto" required>
                <textarea name="description" placeholder="Descripción"></textarea>
                <input type="number" step="0.01" name="price" placeholder="Precio" required>
                <label>Stock:</label>
                <input type="number" name="stock" placeholder="Stock" min="0" value="0" required>
                <label>Imagen:</label>
                <input type="file" name="image" accept="image/*">
                <button type="submit" class="btn btn-green">Guardar</button>
            </form>
        </div>
    </div>

    <!-- Modal Create Collaborator -->
    <div id="modal-collab" class="modal">
        <div class="modal-content">
            <span onclick="document.getElementById('modal-collab').style.display='none'" style="float:right; cursor:pointer;">&times;</span>
            <h2>Nuevo Colaborador</h2>
            <form action="actions/collab_create.php" method="POST">
                <input type="text" name="name" placeholder="Nombre" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit" class="btn btn-blue">Crear</button>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Product -->
    <div id="modal-edit-product" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modal-edit-product').style.display='none'">&times;</span>
            <h2>Editar Producto</h2>
            <form action="actions/product_update.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit-prod-id">
                <label>Nombre:</label>
                <input type="text" name="name" id="edit-prod-name" required>
                <label>Descripción:</label>
                <textarea name="description" id="edit-prod-desc"></textarea>
                <label>Precio:</label>
                <input type="number" step="0.01" name="price" id="edit-prod-price" required>
                <label>Stock:</label>
                <input type="number" name="stock" id="edit-prod-stock" min="0" required>
                <label>Imagen (Opcional - dejar vacío para mantener actual):</label>
                <input type="file" name="image" accept="image/*">
                <button type="submit" class="btn btn-blue">Actualizar</button>
            </form>
        </div>
    </div>
    
    <script>
    // Initialize Feather Icons
    feather.replace();

    // Sidebar Toggle Logic
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
        });
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }
    }

    // Restore tab on load from Hash or LocalStorage
    document.addEventListener('DOMContentLoaded', () => {
        // Priority 1: URL Hash (e.g. #products)
        const hash = window.location.hash.replace('#', '');
        if(hash && document.getElementById('tab-' + hash)) {
            switchTab(hash);
        } else {
            // Priority 2: LocalStorage
            const savedTab = localStorage.getItem('dashboard_tab');
            if(savedTab && document.getElementById('tab-' + savedTab)) {
                switchTab(savedTab);
            }
        }
    });

    // Listen for hash changes (Sidebar links)
    window.addEventListener('hashchange', () => {
        const hash = window.location.hash.replace('#', '');
        if(hash && document.getElementById('tab-' + hash)) {
            switchTab(hash);
        }
    });

    function switchTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
        // Show target tab
        document.getElementById('tab-' + tabName).style.display = 'block';
        
        // Update active link state
        document.querySelectorAll('.tab-link').forEach(link => {
            link.classList.remove('active');
            if(link.getAttribute('onclick').includes("'" + tabName + "'")) {
                link.classList.add('active');
            }
        });

        // Close Sidebar on Mobile if open
        const sidebar = document.querySelector('.sidebar');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        if (sidebar && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        }
        
        // Save to localStorage
        localStorage.setItem('dashboard_tab', tabName);
    }

    function openEditProduct(id, name, desc, price, stock) {
        document.getElementById('edit-prod-id').value = id;
        document.getElementById('edit-prod-name').value = name;
        document.getElementById('edit-prod-desc').value = desc;
        document.getElementById('edit-prod-price').value = price;
        document.getElementById('edit-prod-stock').value = stock;
        document.getElementById('modal-edit-product').style.display = 'block';
    }

    // Close modal if clicked outside
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = "none";
        }
    }
    </script>
</body>
</html>
