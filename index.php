<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$categories = get_categories();
$featured_products = get_featured_products();

// Optimización: obtener todo lo necesario en bloque para evitar consultas dentro de bucles
$featured_ids = array_column($featured_products, 'id');
$featured_ids_str = implode(',', array_map('intval', $featured_ids));

// 1. Todas las imágenes de productos destacados
$images_by_product = [];
if (!empty($featured_ids_str)) {
    $stmt = $pdo->query("SELECT product_id, image_path FROM product_images WHERE product_id IN ($featured_ids_str) ORDER BY is_main DESC");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $images_by_product[$row['product_id']][] = $row['image_path'];
    }
}

// 2. Todos los aromas de productos destacados
$aromas_by_product = [];
if (!empty($featured_ids_str)) {
    $stmt = $pdo->query("SELECT pa.producto_id, a.id, a.nombre FROM producto_aroma pa INNER JOIN aromas a ON pa.aroma_id = a.id WHERE pa.producto_id IN ($featured_ids_str)");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $aromas_by_product[$row['producto_id']][] = ['id' => $row['id'], 'nombre' => $row['nombre']];
    }
}

// 3. Todas las subcategorías de todas las categorías
$all_subcategories = [];
$stmt = $pdo->query("SELECT * FROM subcategories ORDER BY category_id, name");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $all_subcategories[$row['category_id']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require_once 'components/head.php'; ?>
<title>Rincón de Freya - Productos Herbales, Aromas y Bienestar</title>
<meta name="description" content="Tienda online de productos herbales, aromas, amuletos y bienestar. Descubre productos ancestrales, naturales y exclusivos en Rincón de Freya.">
<meta name="keywords" content="herbolario, aromas, bienestar, amuletos, productos naturales, esencias, sahumerios, espiritualidad, tienda online">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://www.rincondefreya.com.ar/">
<link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">

<!-- Open Graph / Facebook -->
<meta property="og:title" content="Rincón de Freya - Productos Herbales, Aromas y Bienestar">
<meta property="og:description" content="Tienda online de productos herbales, aromas, amuletos y bienestar. Descubre productos ancestrales, naturales y exclusivos en Rincón de Freya.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://www.rincondefreya.com.ar/">
<meta property="og:image" content="https://www.rincondefreya.com.ar/assets/images/logo.jpg">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Rincón de Freya - Productos Herbales, Aromas y Bienestar">
<meta name="twitter:description" content="Tienda online de productos herbales, aromas, amuletos y bienestar. Descubre productos ancestrales, naturales y exclusivos en Rincón de Freya.">
<meta name="twitter:image" content="https://www.rincondefreya.com.ar/assets/images/logo.jpg">

<style>
    .product-carousel {
        height: 100%;
        overflow: hidden;
        position: relative;
    }
    .product-carousel .carousel-inner {
        height: 100%;
    }
    .product-carousel .carousel-item {
        height: 100%;
        text-align: center;
    }
    .product-carousel .carousel-item img {
        height: 100%;
        width: auto;
        max-width: 100%;
        object-fit: contain;
    }
    .carousel-control-prev, .carousel-control-next {
        width: 30px;
        background: rgba(0,0,0,0.2);
        border-radius: 50%;
        height: 30px;
        top: 50%;
        transform: translateY(-50%);
    }
    .single-product-image {
        height: 100%;
        object-fit: contain;
        width: 100%;
    }
</style>
</head>
<body>
    <?php require_once 'components/nav.php'; ?>
    <main class="container mt-4">
        <section class="hero-section" aria-label="Bienvenida Rincón de Freya">
            <div class="hero-content text-center text-white">
                <h1>Bienvenidos a Rincón de Freya</h1>
                <p class="lead">Descubre nuestra colección de productos herbales, aromas y amuletos ancestrales para tu bienestar.</p>
            </div>
        </section>
        <br>
        <!-- Banner Instagram -->
        <div class="row justify-content-center mb-3" aria-label="Banner Instagram">
            <div class="col-md-8">
                <div class="alert alert-info d-flex align-items-center justify-content-center gap-3 p-3 shadow-sm" style="background: linear-gradient(90deg, #fdf6ee 0%, #e3f2fd 100%); border: 1.5px solid #fd7e14; border-radius: 1.5em;">
                    <span style="font-size: 2.1rem; background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: flex; align-items: center;"><i class="bi bi-instagram"></i></span>
                    <span style="font-size: 1.15rem; color: #222;">
                        Seguinos en <b>Instagram</b> para novedades, sorteos y tips:<br>
                        <a href="https://www.instagram.com/rinconfreya/" target="_blank" rel="noopener" style="color: #dc2743; font-weight: 600; text-decoration: underline;">@rinconfreya</a>
                    </span>
                </div>
            </div>
        </div>
        <!-- Fin Banner Instagram -->
        <section class="row justify-content-center mb-4" aria-label="Buscador de productos">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="searchProducts" class="form-control" placeholder="Buscar productos...">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                </div>
                <div id="searchResults" class="position-absolute bg-white shadow-sm rounded p-2" style="display: none; z-index: 1000; width: 100%;"></div>
            </div>
        </div>

        <!-- Sección de Productos Destacados -->
        <h2 class="text-center mb-4 mt-3">Productos Destacados</h2>
        <section class="row g-4" aria-label="Productos destacados">
        <?php if (empty($featured_products)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                No hay productos destacados disponibles.
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($featured_products as $product): 
            // Usar imágenes precargadas
            $images = isset($images_by_product[$product['id']]) ? $images_by_product[$product['id']] : [];
            if (empty($images) && !empty($product['image'])) {
                $images = [$product['image']];
            }
            // Usar aromas precargados
            $aromas = isset($aromas_by_product[$product['id']]) ? $aromas_by_product[$product['id']] : [];
        ?>
        <div class="col-md-4">
            <div class="card product-card h-100">
                <?php if (!empty($images)): ?>
                    <div class="position-relative">
                        <?php if (!empty($product['price_sale']) && $product['price_sale'] > 0): ?>
                            <div class="position-absolute top-0 start-0 bg-danger text-white px-2 py-1 rounded-end shadow" style="z-index: 10; font-size: 0.75em; font-weight: bold;">
                                LIQUIDACIÓN
                            </div>
                        <?php endif; ?>
                        <?php if (count($images) > 1): ?>
                            <div id="carousel-<?= $product['id'] ?>" class="carousel slide product-carousel" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <?php foreach($images as $index => $image): ?>
                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                            <img src="<?= htmlspecialchars($image) ?>" 
                                                 class="d-block w-100" 
                                                 alt="<?= htmlspecialchars($product['name']) ?> - Foto producto Rincón de Freya"
                                                 loading="lazy">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= $product['id'] ?>" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= $product['id'] ?>" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Siguiente</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($images[0]) ?>" 
                                 class="single-product-image" 
                                 alt="<?= htmlspecialchars($product['name']) ?> - Foto producto Rincón de Freya"
                                 loading="lazy">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="card-body">
                    <!-- Badge de categoría -->
                    <div class="mb-2">
                        <a href="category.php?id=<?= $product['category_id'] ?>" class="text-decoration-none">
                            <span class="badge bg-warning text-dark">
                                Ver más de <?= htmlspecialchars($product['category_name']) ?>
                            </span>
                        </a>
                    </div>
                    
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <div class="description-container">
                        <?php if (!empty($aromas)): ?>
                            <select class="form-select aroma-select mb-2" data-product-id="<?= $product['id'] ?>">
                                <option value="">Elegí un aroma</option>
                                <?php foreach ($aromas as $aroma): ?>
                                    <option value="<?= $aroma['id'] ?>"><?= htmlspecialchars($aroma['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <?php 
                        $full_description = htmlspecialchars($product['description']);
                        $short_description = mb_substr($full_description, 0, 200);
                        $has_more = strlen($full_description) > 200;
                        ?>
                       <p class="card-text">
                            <span class="short-description" style="white-space: pre-line;"><?php echo $short_description; ?></span>
                            <span class="ellipsis"><?php echo $has_more ? '...' : ''; ?></span>
                            <span class="full-description" style="display: none; white-space: pre-line;"><?php echo $full_description; ?></span>
                            <?php if ($has_more): ?>
                            <button class="btn btn-link p-0 ver-mas" onclick="toggleDescription(this)" data-showing-full="false">Ver más</button>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center flex-wrap">
                            <?php if (!empty($product['price_sale']) && $product['price_sale'] > 0): ?>
                                <div class="d-flex flex-column me-2">
                                    <span class="price text-decoration-line-through text-muted small"><?php echo format_price($product['price']); ?></span>
                                    <span class="price text-danger fw-bold"><?php echo format_price($product['price_sale']); ?></span>
                                </div>
                            <?php else: ?>
                                <span class="price me-2"><?php echo format_price($product['price']); ?></span>
                            <?php endif; ?>
                            <small class="text-muted stock-display" data-product-id="<?php echo $product['id']; ?>">
                                (Stock: <?php echo $product['stock']; ?>)
                            </small>
                        </div>
                        <button class="btn btn-primary btn-sm add-to-cart" 
                            data-product-id="<?php echo $product['id']; ?>"
                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-product-price="<?php echo (!empty($product['price_sale']) && $product['price_sale'] > 0) ? $product['price_sale'] : $product['price']; ?>"
                            data-product-stock="<?php echo $product['stock']; ?>"
                            data-product-stock-original="<?php echo $product['stock']; ?>"
                            <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                            <i class="bi bi-cart-plus"></i> 
                            <?php echo ($product['stock'] <= 0) ? 'Sin stock' : 'Agregar al carrito'; ?>
                        </button>
                    </div>
                    <!-- Compartir producto -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-center gap-2">
                            <?php
                                $shareUrl = "https://www.rincondefreya.com.ar/category.php?id={$product['category_id']}&product_id={$product['id']}";
                                $shareText = "¡Mirá este producto! " . htmlspecialchars($product['name']);
                            ?>
                            <a href="https://wa.me/?text=<?= urlencode($shareText . ' ' . $shareUrl) ?>"
                            target="_blank" title="Compartir por WhatsApp" class="btn btn-success btn-sm rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>"
                            target="_blank" title="Compartir en Facebook" class="btn btn-primary btn-sm rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="#" onclick="navigator.clipboard.writeText('<?= $shareUrl ?>'); 
                               const toast = document.createElement('div'); 
                               toast.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3'; 
                               toast.style.zIndex = '9999'; 
                               toast.innerHTML = 'Link copiado al portapapeles'; 
                               document.body.appendChild(toast); 
                               setTimeout(() => toast.remove(), 2000); 
                               return false;"
                            title="Copiar link para Instagram" class="btn btn-gradient btn-sm rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); color: white; border: none;">
                                <i class="bi bi-instagram"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </section>
        
        <section class="row mb-5" aria-label="Categorías y subcategorías">
            <?php foreach($categories as $category): 
                $subcategories = isset($all_subcategories[$category['id']]) ? $all_subcategories[$category['id']] : [];
            ?>
            <div class="col-md-3 mb-3">
                <div class="card h-100 category-card">
                    <div class="card-body text-center">
                        <h3 class="card-title">
                            <i class="bi <?php echo htmlspecialchars($category['icon']); ?>" style="color: #fd7e14;"> </i>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </h3>
                        <div class="subcategories-container mb-3">
                            <?php if (!empty($subcategories)): ?>
                                <?php foreach($subcategories as $subcategory): ?>
                                    <a href="category.php?id=<?php echo $category['id']; ?>&sub=<?php echo $subcategory['id']; ?>" 
                                    class="badge text-bg-warning text-decoration-none m-1">
                                        <?php echo htmlspecialchars($subcategory['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">No hay subcategorías disponibles</span>
                            <?php endif; ?>
                        </div>
                        <a href="category.php?id=<?php echo $category['id']; ?>" class="btn btn-primary">Ver productos</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
        
    </main>
                    
    <?php require_once 'components/footer.php'; ?>
    <a href="cart.php" class="floating-cart">
        <i class="bi bi-cart" style="font-size: 24px;"></i>
        <span class="cart-count">0</span>
    </a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- CONTADOR DEL CARRITO ---
            function updateCartCount() {
                const cart = JSON.parse(localStorage.getItem('cart')) || [];
                const count = cart.reduce((total, item) => total + item.quantity, 0);
                document.querySelectorAll('.cart-count').forEach(el => {
                    el.textContent = count;
                });
            }

            // --- Sincroniza el stock visual al cargar la página ---
            function syncStockVisual() {
                const cart = JSON.parse(localStorage.getItem('cart')) || [];
                document.querySelectorAll('.add-to-cart').forEach(function(btn) {
                    var productId = btn.getAttribute('data-product-id');
                    var stockOriginal = parseInt(btn.getAttribute('data-product-stock-original'), 10);
                    
                    // Sumar TODAS las cantidades del mismo producto (con cualquier aroma)
                    let totalQuantity = 0;
                    cart.forEach(item => {
                        if (item.productId == productId) {
                            totalQuantity += parseInt(item.quantity) || 0;
                        }
                    });
                    
                    let stockVisual = stockOriginal - totalQuantity;
                    btn.setAttribute('data-product-stock', stockVisual);

                    // Actualizar el texto de stock
                    const stockDisplay = btn.closest('.card-body').querySelector('.stock-display');
                    if (stockDisplay) {
                        stockDisplay.textContent = `(Stock: ${stockVisual})`;
                    }

                    // Actualizar el botón
                    if (stockVisual <= 0) {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="bi bi-cart-plus"></i> Sin stock';
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-cart-plus"></i> Agregar al carrito';
                    }
                });
            }

            // Ejecutar al cargar la página
            syncStockVisual();
            updateCartCount();

            // SISTEMA ÚNICO DE CARRITO - REEMPLAZAR el sistema de cart.js con el sistema de category.php
            document.querySelectorAll('.add-to-cart').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    var productId = btn.getAttribute('data-product-id');
                    var productName = btn.getAttribute('data-product-name');
                    var productPrice = parseFloat(btn.getAttribute('data-product-price'));
                    var stockOriginal = parseInt(btn.getAttribute('data-product-stock-original'), 10);

                    // Leer aroma seleccionado
                    var aromaSelect = document.querySelector(`.aroma-select[data-product-id="${productId}"]`);
                    var aromaId = aromaSelect ? aromaSelect.value : '';
                    var aromaName = aromaSelect ? aromaSelect.options[aromaSelect.selectedIndex].text : '';

                    // Si el producto tiene aromas, obliga a elegir uno
                    if (aromaSelect && aromaSelect.options.length > 1 && !aromaId) {
                        aromaSelect.classList.add('is-invalid');
                        aromaSelect.focus();
                        return;
                    } else if (aromaSelect) {
                        aromaSelect.classList.remove('is-invalid');
                    }

                    let cart = JSON.parse(localStorage.getItem('cart')) || [];
                    // Buscar si ya existe ese producto con ese aroma
                    let index = cart.findIndex(item => item.productId == productId && item.aromaId == aromaId);
                    let currentQty = index !== -1 ? cart[index].quantity : 0;

                    // Calcular total de ese producto en el carrito
                    let totalQuantity = 0;
                    cart.forEach(item => {
                        if (item.productId == productId) {
                            totalQuantity += parseInt(item.quantity) || 0;
                        }
                    });

                    // Verificar stock disponible
                    if (totalQuantity >= stockOriginal) {
                        alert(`No hay suficiente stock. Disponible: ${stockOriginal - totalQuantity}`);
                        return;
                    }

                    // Agregar al carrito
                    if (index !== -1) {
                        cart[index].quantity += 1;
                    } else {
                        cart.push({
                            productId: productId,
                            productName: productName,
                            productPrice: productPrice,
                            quantity: 1,
                            aromaId: aromaId,
                            aromaName: aromaName
                        });
                    }
                    localStorage.setItem('cart', JSON.stringify(cart));
                    updateCartCount();
                    
                    // Mostrar mensaje de éxito
                    const notification = document.createElement('div');
                    notification.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
                    notification.style.zIndex = '9999';
                    notification.innerHTML = 'Producto agregado al carrito';
                    document.body.appendChild(notification);
                    setTimeout(() => notification.remove(), 2000);

                    // --- Actualiza el stock visual de todos los botones de ese producto ---
                    syncStockVisual();
                });
            });
        });

        // Buscador de productos
        let searchTimeout;
        document.getElementById('searchProducts').addEventListener('input', function(e) {
            const searchTerm = e.target.value.trim();
            const resultsContainer = document.getElementById('searchResults');
            clearTimeout(searchTimeout);
            if (searchTerm.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }
            searchTimeout = setTimeout(() => {
                fetch(`search_products.php?q=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(products => {
                        if (products.length > 0) {
                            const html = products.map(product => `
                                <a href="category.php?id=${product.category_id}&sub=${product.subcategory_id}" class="search-result p-2 border-bottom text-decoration-none text-dark d-block">
                                    <div class="d-flex align-items-center">
                                        ${product.image ? `<img src="${product.image}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">` : ''}
                                        <div>
                                            <h6 class="mb-0">${product.name}</h6>
                                            <small class="text-muted">${product.category_name}</small>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-primary">$${parseFloat(product.price).toFixed(2)}</span>
                                                <small class="${product.stock > 0 ? 'text-success' : 'text-danger'}">
                                                    ${product.stock > 0 ? 'Disponible' : 'Sin stock'}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            `).join('');
                            resultsContainer.innerHTML = html;
                            resultsContainer.style.display = 'block';
                        } else {
                            resultsContainer.innerHTML = '<div class="p-2">No se encontraron productos</div>';
                            resultsContainer.style.display = 'block';
                        }
                    });
            }, 300);
        });

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            const resultsContainer = document.getElementById('searchResults');
            const searchInput = document.getElementById('searchProducts');
            if (!resultsContainer.contains(e.target) && e.target !== searchInput) {
                resultsContainer.style.display = 'none';
            }
        });

        function toggleDescription(button) {
            const container = button.closest('.description-container');
            const shortDesc = container.querySelector('.short-description');
            const fullDesc = container.querySelector('.full-description');
            const ellipsis = container.querySelector('.ellipsis');
            const isShowingFull = button.getAttribute('data-showing-full') === 'true';
        
            if (isShowingFull) {
                shortDesc.style.display = '';
                ellipsis.style.display = '';
                fullDesc.style.display = 'none';
                button.textContent = 'Ver más';
                button.setAttribute('data-showing-full', 'false');
            } else {
                shortDesc.style.display = 'none';
                ellipsis.style.display = 'none';
                fullDesc.style.display = '';
                button.textContent = 'Ver menos';
                button.setAttribute('data-showing-full', 'true');
            }
        }
    </script>
</body>
</html>