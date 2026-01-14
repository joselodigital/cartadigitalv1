<?php
// views/catalog.php

$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$business_id && isset($_GET['slug'])) {
    $stmt = $pdo->prepare("SELECT id FROM businesses WHERE slug = ?");
    $stmt->execute([$_GET['slug']]);
    $res = $stmt->fetch();
    if ($res) $business_id = $res['id'];
}

// Redirect ID to Slug (Canonical URL)
if (isset($_GET['id']) && !isset($_GET['slug'])) {
    $stmt = $pdo->prepare("SELECT slug FROM businesses WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $res = $stmt->fetch();
    if ($res && !empty($res['slug'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        
        // Handle subdirectory "catalogodigital" explicitly if needed, or rely on relative path
        // Assuming .htaccess handles /slug mapping to index.php?view=catalog&slug=slug
        
        // We want to redirect to /catalogodigital/slug
        // But we need to know the base path.
        // If current script is /catalogodigital/index.php, then dirname is /catalogodigital
        
        $new_url = $path . '/' . $res['slug'];
        
        // Avoid infinite loop if somehow we are already there (though the condition !isset($_GET['slug']) helps)
        header("Location: " . $new_url, true, 301);
        exit;
    }
}

if (!$business_id) die("Negocio no encontrado");

$stmt = $pdo->prepare("SELECT * FROM businesses WHERE id = ?");
$stmt->execute([$business_id]);
$business = $stmt->fetch();

if (!$business) die("Negocio no encontrado");

if ($business['status'] === 'suspended') {
    die("<div class='suspended-container'>
            <div class='suspended-content'>
                <div class='suspended-icon'>⚠️</div>
                <h2 class='suspended-title'>Negocio Suspendido</h2>
                <p class='suspended-text'>Este catálogo no está disponible temporalmente.</p>
            </div>
         </div>");
}

if ($business['status'] !== 'active') die("Negocio no disponible");

// Plan checks
$stmt = $pdo->prepare("SELECT branding_hidden, allow_private_catalog FROM plans WHERE slug = ?");
$stmt->execute([$business['plan']]);
$plan_features = $stmt->fetch();

$branding_hidden = $plan_features ? $plan_features['branding_hidden'] : 0;
$allow_private_catalog = $plan_features ? $plan_features['allow_private_catalog'] : 0;

// Privacy Check
if ($allow_private_catalog && !empty($business['is_private']) && $business['is_private'] == 1) {
    session_start();
    $session_key = 'catalog_access_' . $business_id;
    if (empty($_SESSION[$session_key])) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['catalog_password'])) {
            if ($_POST['catalog_password'] === $business['password']) {
                $_SESSION[$session_key] = true;
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $error = "Contraseña incorrecta";
            }
        }
        include 'views/partials/password_gate.php';
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE business_id = ? ORDER BY created_at DESC");
$stmt->execute([$business_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Business Type Terminology
$isService = (isset($business['business_type']) && $business['business_type'] == 'service');

// Currency Handling
$currency_code = $business['currency'] ?? 'PEN';
$currency_symbols = [
    'PEN' => 'S/', 'USD' => '$', 'EUR' => '€', 'MXN' => '$', 
    'COP' => '$', 'ARS' => '$', 'CLP' => '$', 'BOB' => 'Bs.', 
    'BRL' => 'R$', 'GTQ' => 'Q'
];
$currency_symbol = $currency_symbols[$currency_code] ?? '$';

$lang = [
    'search_placeholder' => $isService ? 'Buscar servicios...' : '¿Qué estás buscando hoy?',
    'catalog_title' => $isService ? 'Nuestros Servicios Profesionales' : 'Colección Exclusiva',
    'no_results' => $isService ? 'No encontramos servicios con ese nombre' : 'No encontramos productos con ese nombre',
    'empty_catalog' => $isService ? 'Aún no hay servicios disponibles' : 'Pronto agregaremos productos increíbles',
    'add_to_cart' => $isService ? 'Agendar Cita' : 'Agregar al Pedido',
    'cart_title' => $isService ? 'Tu Solicitud' : 'Tu Pedido',
    'modal_add' => $isService ? 'Agendar Ahora' : 'Agregar al Carrito',
    'modal_ask' => 'Consultar Detalles',
    'delivery_label' => $isService ? 'Modalidad Preferida' : 'Método de Entrega',
    'delivery_opt1' => $isService ? 'A Domicilio / Virtual' : 'Entrega a Domicilio',
    'delivery_opt2' => $isService ? 'En Consultorio / Oficina' : 'Retiro en Tienda',
    'address_label' => $isService ? 'Detalles de Ubicación *' : 'Dirección de Entrega *',
    'address_placeholder' => $isService ? 'Dirección o enlace de reunión...' : 'Calle, Número, Referencias...',
    'send_order' => $isService ? 'Confirmar Cita por WhatsApp' : 'Enviar Pedido por WhatsApp',
    'order_summary' => $isService ? 'Solicitud de Cita' : 'Resumen de Compra',
    'empty_cart' => $isService ? 'No has seleccionado servicios' : 'Tu carrito está vacío',
    'whatsapp_title' => $isService ? 'HOLA, QUIERO AGENDAR UNA CITA' : 'HOLA, QUIERO REALIZAR UN PEDIDO',
    'checkout_title' => $isService ? 'Finalizar Solicitud' : 'Completar Pedido',
];
?>
<?php
// Fetch Payment Methods
$stmt = $pdo->prepare("SELECT * FROM business_payment_methods WHERE business_id = ? ORDER BY created_at DESC");
$stmt->execute([$business_id]);
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

$has_payment_methods = count($payment_methods) > 0 || !empty($business['payment_info']) || !empty($business['payment_image']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($business['name']); ?> | Web Oficial</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/catalog_modern.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <meta name="theme-color" content="#2563eb">
    <style>
        :root {
            --primary: <?php echo !empty($business['color_primary']) ? $business['color_primary'] : '#2563eb'; ?>;
            --primary-dark: <?php echo !empty($business['color_primary']) ? $business['color_primary'] : '#1d4ed8'; ?>;
            --bg-body: <?php echo !empty($business['bg_color']) ? $business['bg_color'] : '#f8fafc'; ?>;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="landing-nav">
        <div class="nav-content">
            <div class="nav-brand"><?php echo htmlspecialchars($business['name']); ?></div>
            <div class="nav-links">
                <?php if($business['whatsapp']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $business['whatsapp']); ?>" class="btn-nav-primary" target="_blank">
                        <i data-feather="message-circle" class="nav-icon"></i> <span class="d-none-mobile">Contacto</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-modern">
        <?php if (!empty($business['cover_image'])): ?>
            <div class="hero-cover-image" style="background-image: url('public/uploads/<?php echo htmlspecialchars($business['cover_image']); ?>');"></div>
        <?php else: ?>
            <div class="hero-cover-pattern"></div>
        <?php endif; ?>
        <div class="hero-content-wrapper">
            <div class="hero-profile-frame">
                <?php if($business['logo']): ?>
                    <img src="public/uploads/<?php echo $business['logo']; ?>" alt="Logo" class="hero-logo-img-modern">
                <?php else: ?>
                    <div class="hero-logo-text-modern"><?php echo substr($business['name'], 0, 1); ?></div>
                <?php endif; ?>
            </div>
            
            <h1 class="hero-title-modern"><?php echo htmlspecialchars($business['name']); ?></h1>
            
            <?php if(!empty($business['payment_info'])): ?>
                <p class="hero-subtitle-modern"><?php echo nl2br(htmlspecialchars(substr($business['payment_info'], 0, 150))); ?></p>
            <?php endif; ?>
            
            <a href="#catalog" class="btn-hero-modern">
                Ver Catálogo <i data-feather="arrow-down"></i>
            </a>
        </div>
    </header>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="about-grid">
                <div class="about-text-col">
                    <span class="section-tag">Sobre Nosotros</span>
                    <h2 class="section-heading"><?php echo htmlspecialchars($business['about_title'] ?? 'Tu mejor opción en calidad y servicio'); ?></h2>
                    <p class="about-desc">
                        <?php echo nl2br(htmlspecialchars($business['description'] ?? 'Nos dedicamos a ofrecer productos y servicios de alta calidad, pensando siempre en la satisfacción de nuestros clientes. Contáctanos directamente para una atención personalizada.')); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i data-feather="<?php echo htmlspecialchars($business['feature1_icon'] ?? 'shopping-bag'); ?>"></i></div>
                <h3 class="feature-title"><?php echo htmlspecialchars($business['feature1_title'] ?? 'Fácil de Comprar'); ?></h3>
                <p class="feature-desc"><?php echo htmlspecialchars($business['feature1_desc'] ?? 'Elige tus productos favoritos y pídelos directamente por WhatsApp.'); ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i data-feather="<?php echo htmlspecialchars($business['feature2_icon'] ?? 'shield'); ?>"></i></div>
                <h3 class="feature-title"><?php echo htmlspecialchars($business['feature2_title'] ?? 'Compra Segura'); ?></h3>
                <p class="feature-desc"><?php echo htmlspecialchars($business['feature2_desc'] ?? 'Tratas directamente con nosotros, sin intermediarios ni comisiones.'); ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i data-feather="<?php echo htmlspecialchars($business['feature3_icon'] ?? 'message-circle'); ?>"></i></div>
                <h3 class="feature-title"><?php echo htmlspecialchars($business['feature3_title'] ?? 'Atención Personal'); ?></h3>
                <p class="feature-desc"><?php echo htmlspecialchars($business['feature3_desc'] ?? 'Te atendemos personalmente para confirmar detalles de tu pedido.'); ?></p>
            </div>
        </div>
    </section>

    <!-- Catalog Section -->
    <section id="catalog" class="products-section">
        <div class="section-header-modern">
            <h2 class="section-title-modern">Nuestros Productos Destacados</h2>
            <p class="section-desc-modern">Explora nuestra selección exclusiva. Si te interesa algo, contáctanos directamente por WhatsApp.</p>
        </div>

        <div class="search-container-modern">
            <i data-feather="search" class="search-icon-modern"></i>
            <input type="text" id="searchInput" class="search-input-modern" placeholder="Buscar producto..." onkeyup="filterProducts()">
        </div>

        <?php if(count($products) > 0): ?>
            <div class="products-grid" id="productGrid">
                <?php foreach ($products as $p): ?>
                    <div class="product-card" data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>">
                        <div class="card-img-wrapper" onclick="openModal(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                            <?php if($p['image']): ?>
                                <img src="public/uploads/<?php echo $p['image']; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="card-img" loading="lazy">
                            <?php else: ?>
                                <div class="img-placeholder">
                                    <i data-feather="image" style="width:48px;height:48px;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title" onclick="openModal(<?php echo htmlspecialchars(json_encode($p)); ?>)"><?php echo htmlspecialchars($p['name']); ?></h3>
                            <p class="card-desc-preview"><?php echo htmlspecialchars(substr($p['description'], 0, 80)) . '...'; ?></p>
                            
                            <div class="card-footer">
                                <div class="card-price"><?php echo $currency_symbol . number_format($p['price'], 2); ?></div>
                                <div class="card-actions">
                                    <button class="btn-cart-card" onclick="addToCart(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                                        <i data-feather="shopping-cart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Mobile Pagination Controls -->
            <div id="paginationControls" style="display:none; text-align:center; margin-top:30px; padding-bottom: 20px;">
                <div style="display: inline-flex; align-items: center; gap: 15px; background: var(--bg-surface); padding: 8px 20px; border-radius: 50px; box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
                    <button id="btnPrevPage" class="btn-pagination-control" style="background:none; border:none; cursor:pointer; padding:5px; color:var(--text-secondary);">
                        <i data-feather="chevron-left"></i>
                    </button>
                    <span id="pageIndicator" style="font-weight:600; font-size:0.95rem; color:var(--text-main); min-width: 60px; text-align:center;">
                        1 / 1
                    </span>
                    <button id="btnNextPage" class="btn-pagination-control" style="background:none; border:none; cursor:pointer; padding:5px; color:var(--text-main);">
                        <i data-feather="chevron-right"></i>
                    </button>
                </div>
            </div>

            <div id="noResults" class="empty-state-container" style="display:none;">
                <i data-feather="search" class="empty-state-icon opacity-50"></i>
                <h3><?php echo $lang['no_results']; ?></h3>
                <p class="empty-state-text">Intenta con otros términos de búsqueda.</p>
            </div>
        <?php else: ?>
            <div class="empty-state-container">
                <i data-feather="box" class="empty-state-icon"></i>
                <h2><?php echo $lang['empty_catalog']; ?></h2>
                <p class="empty-state-text">Vuelve pronto para ver nuestras novedades.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer class="footer-full">
        <div class="footer-content">
            <span class="footer-logo"><?php echo htmlspecialchars($business['name']); ?></span>
            
            <div class="footer-socials">
                <?php if($business['whatsapp']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $business['whatsapp']); ?>" class="footer-social-link" target="_blank"><i data-feather="message-circle"></i></a>
                <?php endif; ?>
                <?php if(!empty($business['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($business['facebook']); ?>" class="footer-social-link" target="_blank"><i data-feather="facebook"></i></a>
                <?php endif; ?>
                <?php if(!empty($business['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($business['instagram']); ?>" class="footer-social-link" target="_blank"><i data-feather="instagram"></i></a>
                <?php endif; ?>
                 <?php if(!empty($business['tiktok'])): ?>
                    <a href="<?php echo htmlspecialchars($business['tiktok']); ?>" class="footer-social-link" target="_blank">
                        <span class="tiktok-badge">Tk</span>
                    </a>
                <?php endif; ?>
            </div>

            <?php if(!$branding_hidden): ?>
                <div class="footer-copy">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($business['name']); ?>. <br>
                    <span class="footer-attribution">Tecnología por <strong>Joselo Digital</strong></span>
                </div>
            <?php endif; ?>
        </div>
    </footer>

    <!-- Product Modal -->
    <div id="productModal" class="modal-backdrop" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal(event)"><i data-feather="x"></i></button>
            
            <div class="modal-image-col">
                <img id="modalImg" src="" alt="">
            </div>
            
            <div class="modal-info-col">
                <h2 id="modalTitle" class="modal-title"></h2>
                <div id="modalPrice" class="modal-price"></div>
                <div id="modalDesc" class="modal-desc"></div>
                
                <div class="modal-buttons">
                    <button id="modalAddCartBtn" class="modal-btn full-width">
                        <i data-feather="shopping-cart"></i> <?php echo $lang['modal_add']; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toastNotification" class="toast-notification">
        <div class="toast-icon"><i data-feather="check-circle"></i></div>
        <span id="toastMessage">Producto agregado</span>
    </div>

    <!-- Cart Floating Button -->
    <div id="cartFab" class="cart-fab" onclick="openCart()">
        <i data-feather="shopping-bag"></i>
        <span id="cartCount" class="cart-count">0</span>
    </div>

    <!-- Cart Modal -->
    <div id="cartModal" class="modal-backdrop" onclick="closeCart(event)">
        <div class="modal-content cart-modal-content" onclick="event.stopPropagation()">
            <div class="cart-header">
                <h2 id="cartTitle" class="cart-title"><?php echo $lang['cart_title']; ?></h2>
                <button onclick="closeCart(event)" class="btn-close-plain"><i data-feather="x"></i></button>
            </div>
            
            <!-- Step 1: Items -->
            <div id="cartItemsWrapper" class="cart-body-wrapper">
                <div id="cartItems" class="cart-items">
                    <!-- Items injected via JS -->
                    <div class="empty-cart-msg"><?php echo $lang['empty_cart']; ?> 😔</div>
                </div>

                <div class="cart-footer">
                    <div class="cart-total">
                        <span>Total:</span>
                        <span id="cartTotalAmount"><?php echo $currency_symbol; ?>0.00</span>
                    </div>
                    <button onclick="showCheckout()" class="modal-btn full-width">
                        Continuar <i data-feather="arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Form -->
            <div id="cartForm" class="cart-form">
                <button class="btn-back" onclick="hideCheckout()">
                    <i data-feather="arrow-left"></i> Volver al pedido
                </button>
                
                <div class="form-group">
                    <label>Tu Nombre *</label>
                    <input type="text" id="custName" placeholder="Ej. Juan Pérez">
                </div>
                <div class="form-group">
                    <label>Teléfono / WhatsApp *</label>
                    <input type="tel" id="custPhone" placeholder="Ej. 55 1234 5678">
                </div>
                <div class="form-group">
                    <label><?php echo $lang['address_label']; ?></label>
                    <textarea id="custAddress" placeholder="<?php echo $lang['address_placeholder']; ?>"></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['delivery_label']; ?></label>
                    <select id="custDelivery">
                        <option value="<?php echo $lang['delivery_opt1']; ?>"><?php echo $lang['delivery_opt1']; ?></option>
                        <option value="<?php echo $lang['delivery_opt2']; ?>"><?php echo $lang['delivery_opt2']; ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notas Adicionales</label>
                    <textarea id="custNotes" placeholder="Detalles extra (ej. horario preferido)..." class="form-textarea-sm"></textarea>
                </div>

                <button onclick="checkoutWhatsApp()" class="modal-btn full-width mt-20">
                    <i data-feather="message-circle"></i> <?php echo $lang['send_order']; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal-backdrop" onclick="this.style.display='none'">
        <div class="modal-content cart-modal-content" onclick="event.stopPropagation()">
            <div class="cart-header">
                <h2 class="cart-title">Métodos de Pago</h2>
                <button onclick="document.getElementById('paymentModal').style.display='none'" class="btn-close-plain"><i data-feather="x"></i></button>
            </div>
            
            <div style="padding:20px; flex:1; overflow-y:auto;">
                <!-- New Structured Methods -->
                <?php if(count($payment_methods) > 0): ?>
                    <div class="payment-modal-grid">
                        <?php foreach($payment_methods as $pm): ?>
                            <div class="payment-method-card">
                                <div class="payment-method-header">
                                    <?php if($pm['qr_image']): ?>
                                        <img src="public/uploads/<?php echo $pm['qr_image']; ?>" onclick="viewImage('public/uploads/<?php echo $pm['qr_image']; ?>')" class="payment-method-img">
                                    <?php else: ?>
                                        <div class="payment-method-icon-box"><i data-feather="credit-card" style="color:#64748b;"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="payment-method-title"><?php echo htmlspecialchars($pm['method_name']); ?></h3>
                                    </div>
                                </div>
                                <?php if($pm['account_details']): ?>
                                    <div class="payment-method-details"><?php echo htmlspecialchars($pm['account_details']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>

            <div style="padding:15px; border-top:1px solid #eee;">
                <button onclick="document.getElementById('paymentModal').style.display='none'" class="modal-btn full-width">
                    Entendido
                </button>
            </div>
        </div>
    </div>
    
    <!-- Image Preview Modal -->
    <div id="imgPreviewModal" class="modal-backdrop" onclick="this.style.display='none'" style="z-index: 2000;">
        <div style="display:flex; justify-content:center; align-items:center; height:100%;">
            <img id="previewImg" src="" style="max-width:90%; max-height:90%; border-radius:8px; box-shadow:0 0 20px rgba(0,0,0,0.5);">
        </div>
    </div>

    <script>
    function viewImage(src) {
        document.getElementById('previewImg').src = src;
        document.getElementById('imgPreviewModal').style.display = 'block';
    }
    </script>

    <!-- Sticky WhatsApp CTA -->
    <?php if($business['whatsapp']): ?>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $business['whatsapp']); ?>" class="fab-whatsapp" target="_blank" title="Chatear">
            <svg viewBox="0 0 32 32"><path d="M16 2C8.269 2 2 8.269 2 16c0 2.479.648 4.814 1.789 6.84L2.348 29.65l6.98-1.83C11.233 28.896 13.566 29.5 16 29.5c7.731 0 14-6.269 14-14S23.731 2 16 2zm0 25c-2.146 0-4.184-.59-5.969-1.615l-.427-.246-4.426 1.161 1.182-4.31-.279-.443C5.035 20.086 4.5 18.096 4.5 16c0-6.341 5.159-11.5 11.5-11.5s11.5 5.159 11.5 11.5-5.159 11.5-11.5 11.5z"/></svg>
        </a>
    <?php endif; ?>

    <script>
        feather.replace();

        // Cart Logic
        const currencySymbol = "<?php echo $currency_symbol; ?>";
        const businessPhone = "<?php echo preg_replace('/[^0-9]/', '', $business['whatsapp']); ?>";
        const businessId = <?php echo $business_id; ?>;
        let cart = JSON.parse(localStorage.getItem('cart_<?php echo $business_id; ?>')) || [];

        function updateCartUI() {
            const count = cart.reduce((acc, item) => acc + item.qty, 0);
            document.getElementById('cartCount').textContent = count;
            document.getElementById('cartFab').classList.toggle('visible', count > 0);
            localStorage.setItem('cart_<?php echo $business_id; ?>', JSON.stringify(cart));
        }

        function showToast(message) {
            const toast = document.getElementById('toastNotification');
            const msgEl = document.getElementById('toastMessage');
            if(msgEl) msgEl.textContent = message;
            if(toast) {
                toast.classList.add('show');
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }
        }

        function addToCart(product) {
            const existing = cart.find(item => item.id === product.id);
            const currentQty = existing ? existing.qty : 0;
            const maxStock = parseInt(product.stock) || 0;

            if (currentQty + 1 > maxStock) {
                alert('¡Lo sentimos! No hay más stock disponible de este producto.');
                return;
            }

            if (existing) {
                existing.qty++;
            } else {
                cart.push({ ...product, qty: 1 });
            }
            updateCartUI();
            
            // Visual feedback
            const fab = document.getElementById('cartFab');
            fab.style.transform = 'scale(1.2)';
            setTimeout(() => fab.style.transform = 'scale(1)', 200);
            
            // Close product modal if open
            const activeModal = document.querySelector('.modal-backdrop.active');
            if (activeModal) {
                closeModal({target: activeModal});
            }

            // Show Toast
            showToast('<?php echo $isService ? "Servicio agregado" : "Producto agregado"; ?>');
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
            renderCartItems();
        }

        function updateQty(index, change) {
            const item = cart[index];
            const maxStock = parseInt(item.stock) || 0;
            const newQty = item.qty + change;

            if (change > 0 && newQty > maxStock) {
                alert('¡Lo sentimos! No puedes agregar más unidades que el stock disponible (' + maxStock + ').');
                return;
            }

            if (newQty > 0) {
                item.qty = newQty;
            } else {
                // If reducing to 0, confirm remove
                if(confirm('¿Eliminar producto?')) {
                    cart.splice(index, 1);
                }
            }
            updateCartUI();
            renderCartItems();
        }

        function openCart() {
            renderCartItems();
            document.getElementById('cartModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            // Reset to step 1
            document.getElementById('cartItemsWrapper').style.display = 'flex';
            document.getElementById('cartForm').style.display = 'none';
        }

        function closeCart(e) {
            // Check if backdrop clicked OR button clicked (including icon inside)
            if (e.target === e.currentTarget || e.currentTarget.tagName === 'BUTTON' || e.target.closest('button')) {
                document.getElementById('cartModal').classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function renderCartItems() {
            const container = document.getElementById('cartItems');
            const totalEl = document.getElementById('cartTotalAmount');
            
            if (cart.length === 0) {
                container.innerHTML = `<div class="empty-cart-msg"><?php echo $lang['empty_cart']; ?> 😔</div>`;
                totalEl.textContent = currencySymbol + '0.00';
                return;
            }

            let html = '';
            let total = 0;

            cart.forEach((item, index) => {
                const itemTotal = item.price * item.qty;
                total += itemTotal;
                const img = item.image ? `public/uploads/${item.image}` : 'https://via.placeholder.com/60?text=IMG';
                
                html += `
                    <div class="cart-item">
                        <img src="${img}" class="cart-item-img" alt="${item.name}">
                        <div class="cart-item-details">
                            <div class="cart-item-title">${item.name}</div>
                            <div class="cart-item-price">${currencySymbol}${parseFloat(item.price).toFixed(2)}</div>
                            <div class="cart-controls">
                                <button class="qty-btn" onclick="updateQty(${index}, -1)">-</button>
                                <span class="qty-value">${item.qty}</span>
                                <button class="qty-btn" onclick="updateQty(${index}, 1)">+</button>
                                <button class="qty-btn remove" onclick="removeFromCart(${index})"><i data-feather="trash-2" class="icon-sm"></i></button>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
            totalEl.textContent = currencySymbol + total.toFixed(2);
            feather.replace();
        }

        function showCheckout() {
            if (cart.length === 0) return alert('El carrito está vacío');
            document.getElementById('cartItemsWrapper').style.display = 'none';
            document.getElementById('cartForm').style.display = 'block';
        }

        function hideCheckout() {
            document.getElementById('cartItemsWrapper').style.display = 'flex';
            document.getElementById('cartForm').style.display = 'none';
        }

        async function checkoutWhatsApp() {
            const name = document.getElementById('custName').value.trim();
            const phone = document.getElementById('custPhone').value.trim();
            const address = document.getElementById('custAddress').value.trim();
            const delivery = document.getElementById('custDelivery').value;
            const notes = document.getElementById('custNotes').value.trim();

            if (!name || !phone || !address) {
                alert('Por favor completa los campos obligatorios (*)');
                return;
            }

            const sendBtn = document.querySelector('#cartForm .modal-btn');
            const originalBtnText = sendBtn.innerHTML;
            sendBtn.innerHTML = 'Procesando...';
            sendBtn.disabled = true;

            try {
                // Reduce Stock
                const stockResponse = await fetch('actions/reduce_stock.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        business_id: businessId,
                        items: cart.map(i => ({ id: i.id, qty: i.qty }))
                    })
                });

                const stockResult = await stockResponse.json();

                if (!stockResult.success) {
                    alert('Error: ' + stockResult.message);
                    sendBtn.innerHTML = originalBtnText;
                    sendBtn.disabled = false;
                    return;
                }

                let message = `*<?php echo $lang['whatsapp_title']; ?>*\n`;
                message += `--------------------------------\n`;
                message += `👤 *Cliente:* ${name}\n`;
                message += `📱 *Tel:* ${phone}\n`;
                message += `📍 *Dirección:* ${address}\n`;
                message += `🚚 *Entrega:* ${delivery}\n`;
                if (notes) message += `📝 *Notas:* ${notes}\n`;
                message += `--------------------------------\n`;
                message += `*PEDIDO:*\n`;

                let total = 0;
                cart.forEach(item => {
                    const subtotal = item.price * item.qty;
                    total += subtotal;
                    message += `• ${item.qty}x ${item.name} (${currencySymbol}${subtotal.toFixed(2)})\n`;
                });

                message += `--------------------------------\n`;
                message += `*TOTAL: ${currencySymbol}${total.toFixed(2)}*\n`;
                message += `--------------------------------\n`;
                message += `Enviado desde el Catálogo Digital`;

                // Track Click
                fetch('actions/track_click.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ business_id: businessId }),
                    keepalive: true
                }).catch(err => console.error('Error tracking click:', err));

                const url = `https://wa.me/${businessPhone}?text=${encodeURIComponent(message)}`;
                window.open(url, '_blank');
                
                // Clear cart and close modal
                cart = [];
                updateCartUI();
                renderCartItems();
                
                // Close modal
                document.getElementById('cartModal').classList.remove('active');
                document.body.style.overflow = '';
                
                // Reset form
                document.getElementById('custName').value = '';
                document.getElementById('custPhone').value = '';
                document.getElementById('custAddress').value = '';
                document.getElementById('custNotes').value = '';

                // Restore button
                sendBtn.innerHTML = originalBtnText;
                sendBtn.disabled = false;

            } catch (error) {
                console.error('Error:', error);
                alert('Hubo un error de conexión. Intenta nuevamente.');
                sendBtn.innerHTML = originalBtnText;
                sendBtn.disabled = false;
            }
        }

        // Product Modal Logic
        function openModal(product) {
            const modal = document.getElementById('productModal');
            const img = document.getElementById('modalImg');
            
            document.getElementById('modalTitle').textContent = product.name;
            document.getElementById('modalPrice').textContent = currencySymbol + parseFloat(product.price).toFixed(2);
            document.getElementById('modalDesc').innerHTML = product.description ? product.description.replace(/\n/g, '<br>') : 'Sin descripción detallada.';
            
            if (product.image) {
                img.src = 'public/uploads/' + product.image;
                img.style.display = 'block';
            } else {
                img.style.display = 'none';
            }
            
            // Update Add to Cart Button in Modal
            const addBtn = document.getElementById('modalAddCartBtn');
            addBtn.onclick = function() {
                addToCart(product);
            };

            // Update Direct WhatsApp Button
            const waBtn = document.getElementById('modalBtn');
            const text = `Hola, me interesa este producto: ${product.name} (${currencySymbol}${product.price})`;
            waBtn.href = `https://wa.me/${businessPhone}?text=${encodeURIComponent(text)}`;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(e) {
            if (e.target === e.currentTarget || e.target.closest('.modal-close') || e.target.classList.contains('modal-backdrop')) {
                document.getElementById('productModal').classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // Search & Pagination Logic
        const mobilePagination = {
            currentPage: 1,
            itemsPerPage: 4,
            isActive: false,
            totalPages: 1,
            
            init: function() {
                this.check();
                window.addEventListener('resize', () => this.check());
                
                const btnPrev = document.getElementById('btnPrevPage');
                const btnNext = document.getElementById('btnNextPage');
                
                if(btnPrev) btnPrev.addEventListener('click', () => this.changePage(-1));
                if(btnNext) btnNext.addEventListener('click', () => this.changePage(1));
            },

            check: function() {
                const searchVal = document.getElementById('searchInput').value.trim();
                if (searchVal !== '') return;

                if (window.innerWidth <= 768) {
                    // Mobile
                    if (!this.isActive) {
                        this.start();
                    } else {
                        this.updateVisibility();
                    }
                } else {
                    // Desktop
                    if (this.isActive) {
                        this.stop();
                    }
                }
            },

            start: function() {
                this.isActive = true;
                this.currentPage = 1; 
                this.updateVisibility();
            },

            stop: function() {
                this.isActive = false;
                document.querySelectorAll('.product-card').forEach(c => c.style.display = 'flex');
                const container = document.getElementById('paginationControls');
                if(container) container.style.display = 'none';
            },

            changePage: function(direction) {
                const newPage = this.currentPage + direction;
                if (newPage >= 1 && newPage <= this.totalPages) {
                    this.currentPage = newPage;
                    this.updateVisibility();
                    // Scroll to top of grid
                    const grid = document.getElementById('productGrid');
                    if(grid) grid.scrollIntoView({behavior: 'smooth', block: 'start'});
                }
            },

            updateVisibility: function() {
                if (!this.isActive) return;

                const cards = Array.from(document.querySelectorAll('.product-card'));
                const totalItems = cards.length;
                this.totalPages = Math.ceil(totalItems / this.itemsPerPage);
                
                // Adjust current page if out of bounds (e.g. after search filter clear)
                if (this.currentPage > this.totalPages) this.currentPage = this.totalPages || 1;
                if (this.currentPage < 1) this.currentPage = 1;

                const start = (this.currentPage - 1) * this.itemsPerPage;
                const end = start + this.itemsPerPage;

                cards.forEach((card, index) => {
                    if (index >= start && index < end) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Update UI Controls
                const container = document.getElementById('paginationControls');
                const indicator = document.getElementById('pageIndicator');
                const btnPrev = document.getElementById('btnPrevPage');
                const btnNext = document.getElementById('btnNextPage');

                if (container && indicator) {
                    // Only show controls if there is more than 1 page
                    if (this.totalPages > 1) {
                        container.style.display = 'block';
                        indicator.textContent = `${this.currentPage} / ${this.totalPages}`;
                        
                        // Update button states
                        if (btnPrev) {
                            btnPrev.disabled = this.currentPage === 1;
                            btnPrev.style.opacity = this.currentPage === 1 ? '0.3' : '1';
                        }
                        if (btnNext) {
                            btnNext.disabled = this.currentPage === this.totalPages;
                            btnNext.style.opacity = this.currentPage === this.totalPages ? '0.3' : '1';
                        }
                    } else {
                        container.style.display = 'none';
                    }
                }
            }
        };

        function filterProducts() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.product-card');
            let hasResults = false;

            if (query.length > 0) {
                // Search Mode: Disable pagination UI
                const container = document.getElementById('paginationControls');
                if(container) container.style.display = 'none';
                
                cards.forEach(card => {
                    const name = card.getAttribute('data-name');
                    if (name.includes(query)) {
                        card.style.display = 'flex'; 
                        hasResults = true;
                    } else {
                        card.style.display = 'none';
                    }
                });
            } else {
                // Search Cleared: Restore appropriate view
                if (window.innerWidth <= 768) {
                    mobilePagination.start();
                    hasResults = cards.length > 0;
                } else {
                    cards.forEach(card => card.style.display = 'flex');
                    hasResults = cards.length > 0;
                    mobilePagination.stop();
                }
            }

            document.getElementById('noResults').style.display = hasResults ? 'none' : 'block';
        }

        // Initialize Pagination
        document.addEventListener('DOMContentLoaded', () => {
            mobilePagination.init();
        });

        // Initialize
        updateCartUI();
    </script>
</body>
</html>
