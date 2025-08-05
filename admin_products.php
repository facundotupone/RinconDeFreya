
<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// --- ENDPOINT AJAX PARA PRODUCTOS DESTACADOS ---
if (isset($_GET['action']) && $_GET['action'] === 'get_featured_products') {
    header('Content-Type: application/json');
    if (function_exists('get_featured_products')) {
        $featured = get_featured_products();
        echo json_encode($featured);
    } else {
        echo json_encode([]);
    }
    exit;
}

// --- ENDPOINT PARA GUARDAR ORDEN DE DESTACADOS ---
if (isset($_GET['action']) && $_GET['action'] === 'save_featured_order') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $success = true;
    if (is_array($data)) {
        foreach ($data as $item) {
            $id = intval($item['id']);
            $destacados = intval($item['destacados']);
            $stmt = $pdo->prepare("UPDATE products SET destacados = ? WHERE id = ?");
            if (!$stmt->execute([$destacados, $id])) {
                $success = false;
            }
        }
    } else {
        $success = false;
    }
    echo json_encode(['success' => $success]);
    exit;
}

$message = '';
$categories = get_categories();

// Obtener todas las subcategorías
$stmtSub = $pdo->query("SELECT s.id, s.name, s.category_id FROM subcategories s ORDER BY s.name");
$subcategories = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los aromas
$stmtAromas = $pdo->query("SELECT id, nombre FROM aromas ORDER BY nombre");
$aromas = $stmtAromas->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario de producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action'])) {
        // Agregar nuevo producto
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $price_sale = isset($_POST['price_sale']) && $_POST['price_sale'] !== '' ? floatval($_POST['price_sale']) : 0;
            $category_id = intval($_POST['category_id']);
            $subcategory_id = !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null;
                $destacados = 0;
            $aromas_sel = isset($_POST['aromas']) ? $_POST['aromas'] : [];

            $pdo->beginTransaction();
            try {
                // Insertar producto sin imagen principal aún
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, price_sale, category_id, subcategory_id, destacados, image) VALUES (?, ?, ?, ?, ?, ?, ?, NULL)");
                $stmt->execute([$name, $description, $price, $price_sale, $category_id, $subcategory_id, $destacados]);
                $product_id = $pdo->lastInsertId();

                if (!empty($aromas_sel)) {
                    $stmtInsert = $pdo->prepare("INSERT INTO producto_aroma (producto_id, aroma_id) VALUES (?, ?)");
                    foreach ($aromas_sel as $aroma_id) {
                        $stmtInsert->execute([$product_id, $aroma_id]);
                    }
                }

                $main_image_path = null;
                if (!empty($_FILES['images']['name'][0])) {
                    $upload_dir = 'assets/images/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_extension = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                            $new_filename = uniqid() . '.' . $file_extension;
                            $image_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($tmp_name, $image_path)) {
                                $is_main = ($key === 0) ? 1 : 0;
                                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)");
                                $stmt->execute([$product_id, $image_path, $is_main]);
                                if ($is_main) {
                                    $main_image_path = $image_path;
                                }
                            }
                        }
                    }
                }
                // Si hay imagen principal, actualizar el campo image en products
                if ($main_image_path) {
                    $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                    $stmt->execute([$main_image_path, $product_id]);
                }
                $pdo->commit();
                $message = 'Producto agregado exitosamente.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Error al agregar el producto: ' . $e->getMessage();
            }
        }
        // Editar producto
        elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $price_sale = !empty($_POST['price_sale']) ? floatval($_POST['price_sale']) : null;
            $category_id = intval($_POST['category_id']);
            $subcategory_id = !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null;
            $destacados = isset($_POST['destacados']) ? 1 : 0;
            $main_image_id = isset($_POST['main_image']) ? intval($_POST['main_image']) : null;
            $aromas_sel = isset($_POST['aromas']) ? $_POST['aromas'] : [];

            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, price_sale = ?, category_id = ?, subcategory_id = ?, destacados = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $price_sale, $category_id, $subcategory_id, $destacados, $id]);

                $pdo->prepare("DELETE FROM producto_aroma WHERE producto_id = ?")->execute([$id]);
                if (!empty($aromas_sel)) {
                    $stmtInsert = $pdo->prepare("INSERT INTO producto_aroma (producto_id, aroma_id) VALUES (?, ?)");
                    foreach ($aromas_sel as $aroma_id) {
                        $stmtInsert->execute([$id, $aroma_id]);
                    }
                }

                if ($main_image_id) {
                    $stmt = $pdo->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = ?");
                    $stmt->execute([$id]);
                    $stmt = $pdo->prepare("UPDATE product_images SET is_main = 1 WHERE id = ?");
                    $stmt->execute([$main_image_id]);
                }

                if (!empty($_FILES['new_images']['name'][0])) {
                    $upload_dir = 'assets/images/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file_extension = strtolower(pathinfo($_FILES['new_images']['name'][$key], PATHINFO_EXTENSION));
                            $new_filename = uniqid() . '.' . $file_extension;
                            $image_path = $upload_dir . $new_filename;

                            if (move_uploaded_file($tmp_name, $image_path)) {
                                $is_main = 0;
                                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)");
                                $stmt->execute([$id, $image_path, $is_main]);
                            }
                        }
                    }
                }

                $pdo->commit();
                $message = 'Producto actualizado exitosamente.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Error al actualizar el producto: ' . $e->getMessage();
            }
        }
        // Eliminar producto
        elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $product_id = intval($_POST['id']);

            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $success = $stmt->execute([$product_id]);

                if ($success) {
                    foreach ($images as $image_path) {
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    $pdo->commit();
                    $message = 'Producto eliminado exitosamente.';
                } else {
                    $pdo->rollBack();
                    $message = 'Error al eliminar el producto.';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Error al eliminar el producto: ' . $e->getMessage();
            }
        }
        // Otras acciones (toggle_featured, update_stock, etc.)
      
        elseif ($_POST['action'] === 'toggle_featured' && isset($_POST['product_id'])) {
            $product_id = intval($_POST['product_id']);
            $current_status = isset($_POST['current_status']) ? intval($_POST['current_status']) : 0;
            $new_status = $current_status ? 0 : 1;

            $stmt = $pdo->prepare("UPDATE products SET destacados = ? WHERE id = ?");
            $stmt->execute([$new_status, $product_id]);
            $message = $new_status ? 'Producto marcado como destacado.' : 'Producto quitado de destacados.';
        }
        // Actualizar stock
        elseif ($_POST['action'] === 'update_stock' && isset($_POST['id'])) {
            $product_id = intval($_POST['id']);
            $change = isset($_POST['change']) ? intval($_POST['change']) : 0;

            // Obtener stock actual
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $current_stock = $stmt->fetchColumn();

            if ($current_stock !== false) {
                $new_stock = max(0, $current_stock + $change);
                $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $stmt->execute([$new_stock, $product_id]);
                $message = 'Stock actualizado correctamente.';
            } else {
                $message = 'Producto no encontrado.';
            }
        }
        // SOBREESCRIBIR STOCK MANUALMENTE
        elseif ($_POST['action'] === 'set_stock' && isset($_POST['id'], $_POST['new_stock'])) {
            $product_id = intval($_POST['id']);
            $new_stock = max(0, intval($_POST['new_stock']));
            $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->execute([$new_stock, $product_id]);
            $message = 'Stock actualizado manualmente.';
        }
    }
}

$stmt = $pdo->query("
    SELECT p.*, c.name as category_name, s.name as subcategory_name,
           (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as image_count
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN subcategories s ON p.subcategory_id = s.id 
    ORDER BY c.name, p.name
");
$products = $stmt->fetchAll();

// Agrupar productos por categoría
$productsByCategory = [];
foreach ($products as $product) {
    $cat = $product['category_name'] ?: 'Sin categoría';
    if (!isset($productsByCategory[$cat])) $productsByCategory[$cat] = [];
    $productsByCategory[$cat][] = $product;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Productos - Rincón Freya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .image-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border: 2px solid #ddd;
            border-radius: 4px;
        }
        .main-image {
            border-color: #ffc107;
        }
        .image-container {
            position: relative;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .delete-image-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">Admin Rincon de Freya</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_products.php">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_categories.php">Categorías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_aromas.php' ? 'active' : ''; ?>" href="admin_aromas.php">Aromas</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <h1>Administrar Productos</h1>
        
        <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <!-- ...formulario de alta de producto igual que tu versión actual... -->
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h5 mb-0">Agregar Nuevo Producto</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="mb-4">
                            <input type="hidden" name="action" value="add">

                            <div class="mb-3 form-floating">
                                <input type="text" class="form-control" id="name" name="name" placeholder="Nombre" required>
                                <label for="name">Nombre</label>
                            </div>
                            <div class="mb-3 form-floating">
                                <textarea class="form-control" id="description" name="description" placeholder="Descripción" style="height: 100px" required></textarea>
                                <label for="description">Descripción</label>
                            </div>
                            <div class="mb-3 form-floating">
                                <input type="number" class="form-control" id="price" name="price" step="0.01" placeholder="Precio" required>
                                <label for="price">Precio</label>
                            </div>
                            <div class="mb-3 form-floating">
                                <input type="number" class="form-control" id="price_sale" name="price_sale" step="0.01" placeholder="Precio Promoción (opcional)">
                                <label for="price_sale">Precio Promoción</label>
                            </div>
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Categoría</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="subcategory_id" class="form-label">Subcategoría</label>
                                <select class="form-select" id="subcategory_id" name="subcategory_id" required>
                                    <option value="">Seleccionar</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="aromas" class="form-label">Aromas</label>
                                <select name="aromas[]" id="aromas" class="form-select" multiple>
                                    <option disabled>Seleccione una subcategoría</option>
                                </select>
                                <small class="text-muted">Ctrl+Click para seleccionar varios.</small>
                            </div>
                            <div class="mb-3">
                                <label for="images" class="form-label">Imágenes (Múltiples)</label>
                                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" required>
                                <small class="text-muted">La primera imagen será la principal</small>
                            </div>
                            <div class="mb-3 form-check">
                                <!-- El input de destacados ha sido removido. -->
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="addProductBtn">Agregar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <h2>Lista de Productos</h2>
                <div class="mb-3">
                    <input type="text" id="searchAdminProducts" class="form-control mb-3" placeholder="Buscar producto por nombre...">
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle shadow-sm" id="products-table">
                        <thead class="table-dark">
                            <tr>
                                <th>Imágenes</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($productsByCategory as $catName => $catProducts): ?>
                            <tr class="table-warning">
                                <td colspan="6"><strong><?= htmlspecialchars($catName) ?></strong></td>
                            </tr>
                            <?php foreach ($catProducts as $product): 
                                $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
                                $stmt->execute([$product['id']]);
                                $images = $stmt->fetchAll();

                                $stmtAroma = $pdo->prepare("SELECT a.id, a.nombre FROM producto_aroma pa INNER JOIN aromas a ON pa.aroma_id = a.id WHERE pa.producto_id = ?");
                                $stmtAroma->execute([$product['id']]);
                                $productAromas = $stmtAroma->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($images)): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($images as $image): ?>
                                                <img src="<?= htmlspecialchars($image['image_path']) ?>"
                                                    class="image-thumbnail <?= $image['is_main'] ? 'main-image' : '' ?>"
                                                    title="<?= $image['is_main'] ? 'Imagen principal' : '' ?>">
                                            <?php endforeach; ?>
                                        </div>
                                        <small><?= count($images) ?> <?= count($images) === 1 ? 'imagen' : 'imágenes' ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Sin imágenes</span>
                                    <?php endif; ?>
                                </td>
                                <td class="product-name">
                                    <span class="fw-bold"><?= htmlspecialchars($product['name']) ?></span>
                                    <?php if (!empty($productAromas)): ?>
                                        <br>
                                        <small class="text-muted">
                                            Aromas: <?= implode(', ', array_map(function($a){ return htmlspecialchars($a['nombre']); }, $productAromas)) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($product['category_name']) ?></span>
                                    <?php if (!empty($product['subcategory_name'])): ?>
                                        <small class="d-block text-muted"><?= htmlspecialchars($product['subcategory_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($product['price_sale']) && $product['price_sale'] > 0): ?>
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <span class="badge bg-danger">LIQUIDACIÓN</span>
                                            <div>
                                                <span class="badge bg-success text-decoration-line-through me-1">$<?= number_format($product['price'], 2) ?></span>
                                                <span class="badge bg-danger fs-6">$<?= number_format($product['price_sale'], 2) ?></span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-success">$<?= number_format($product['price'], 2) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['stock'] == 0): ?>
                                        <span class="badge bg-danger">Sin stock</span>
                                    <?php elseif ($product['stock'] < 5): ?>
                                        <span class="badge bg-warning text-dark"><?= $product['stock'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= $product['stock'] ?></span>
                                    <?php endif; ?>
                                    <div class="d-flex align-items-center mt-2">
                                        <form method="POST" class="me-1">
                                            <input type="hidden" name="action" value="update_stock">
                                            <input type="hidden" name="change" value="-1">
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" <?= $product['stock'] <= 0 ? 'disabled' : '' ?> title="Restar 1" data-bs-toggle="tooltip">-</button>
                                        </form>
                                        <form method="POST" class="d-flex align-items-center mx-2" style="gap:4px;">
                                            <input type="hidden" name="action" value="set_stock">
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                            <input type="number" name="new_stock" value="<?= $product['stock'] ?>" min="0" class="form-control form-control-sm" style="width:60px;">
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar nuevo stock" data-bs-toggle="tooltip">
                                                <i class="bi bi-save"></i>
                                            </button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_stock">
                                            <input type="hidden" name="change" value="1">
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Sumar 1" data-bs-toggle="tooltip">+</button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <button class="btn btn-sm btn-info" title="Editar producto" data-bs-toggle="tooltip"
                                            onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>, <?= htmlspecialchars(json_encode($images)) ?>, <?= htmlspecialchars(json_encode(array_column($productAromas, 'id'))) ?>)">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este producto?')" title="Eliminar producto" data-bs-toggle="tooltip">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </form>
                                        <form method="POST">
                                            <button type="button" class="btn btn-sm <?= $product['destacados'] ? 'btn-warning' : 'btn-outline-secondary' ?>" title="Destacar/Quitar destacado y ordenar" data-bs-toggle="tooltip" onclick="openFeaturedOrderModal(<?= $product['id'] ?>)">
                                                <?= $product['destacados'] ? '<i class="bi bi-star-fill"></i> Destacado' : '<i class="bi bi-star"></i> Destacar' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    <!-- Modal de orden de destacados -->
    <div class="modal fade" id="featuredOrderModal" tabindex="-1" aria-labelledby="featuredOrderModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title" id="featuredOrderModalLabel">Ordenar Productos Destacados</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div id="featuredOrderList" class="list-group">
              <!-- Aquí se cargará la lista drag & drop con JS -->
            </div>
            <small class="text-muted">Arrastrá para reordenar los productos destacados. El primero será el más importante.</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="saveFeaturedOrderBtn">Guardar orden</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark shadow-sm">
                    <h5 class="modal-title ">Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Precio</label>
                                    <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Precio Promoción</label>
                                    <input type="number" class="form-control" id="edit_price_sale" name="price_sale" step="0.01" placeholder="Opcional">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Aromas</label>
                                    <select name="aromas[]" id="edit_aromas" class="form-select" multiple>
                                        <option disabled>Seleccione una subcategoría</option>
                                    </select>
                                    <small class="text-muted">Ctrl+Click para seleccionar varios.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoría</label>
                                    <select class="form-select" id="edit_category_id" name="category_id" required>
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Subcategoría</label>
                                    <select class="form-select" id="edit_subcategory_id" name="subcategory_id">
                                        <option value="">Seleccionar</option>
                                    </select>
                                </div>
                                <div class="mb-3 form-check">
                                    <label class="form-label" for="edit_destacados">Orden Destacado (0 = no destacado, 1-9 = orden)</label>
                                    <input type="number" class="form-control" id="edit_destacados" name="destacados" min="0" max="99" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Imágenes Actuales</label>
                            <div id="current_images_container" class="d-flex flex-wrap gap-3 mb-3"></div>
                            
                            <label class="form-label">Agregar Nuevas Imágenes</label>
                            <input type="file" class="form-control" name="new_images[]" multiple accept="image/*">
                            <small class="text-muted">Seleccione imágenes adicionales (opcional)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Confirmación antes de agregar producto para evitar duplicados
    document.addEventListener('DOMContentLoaded', function() {
        var addForm = document.querySelector('form[method="POST"][enctype="multipart/form-data"].mb-4');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                if (!confirm('¿Estás seguro de que quieres agregar este producto?\nVerifica que no esté repetido.')) {
                    e.preventDefault();
                }
            });
        }
    });
    </script>
    <script>
    // --- Drag & drop y guardado de orden de destacados ---
    let featuredOrderList = null;
    let featuredOrderModal = null;

    // Definir la función en el ámbito global ANTES de cualquier uso
function openFeaturedOrderModal(productId) {
    // Inicializar referencias si aún no existen
    if (!featuredOrderList) featuredOrderList = document.getElementById('featuredOrderList');
    if (!featuredOrderModal) featuredOrderModal = new bootstrap.Modal(document.getElementById('featuredOrderModal'));

    // Mostrar el modal
    featuredOrderModal.show();

    // Cargar productos destacados vía AJAX
    fetch('admin_products.php?action=get_featured_products')
        .then(r => r.json())
        .then(data => {
            // Si el producto no está en destacados, buscarlo en la tabla y agregarlo temporalmente
            const already = data.some(p => String(p.id) === String(productId));
            if (!already) {
                // Buscar datos del producto en la tabla HTML
                const row = document.querySelector(`#products-table tr button[onclick*='openFeaturedOrderModal(${productId})']`);
                if (row) {
                    // Subir al <tr> y obtener datos
                    const tr = row.closest('tr');
                    const name = tr.querySelector('.product-name span.fw-bold')?.textContent || 'Producto';
                    const img = tr.querySelector('img.image-thumbnail')?.getAttribute('src') || 'assets/images/products/default.jpg';
                    data.push({id: productId, name: name, image: img, destacados: data.length+1});
                }
            }
            // Renderizar lista
            featuredOrderList.innerHTML = '';
            data.forEach((prod, idx) => {
                const item = document.createElement('div');
                item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';
                item.setAttribute('data-id', prod.id);
                // No draggable
                item.innerHTML = `
                    <img src="${prod.image || 'assets/images/products/default.jpg'}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                    <span>${prod.name}</span>
                    <span class='badge bg-warning ms-auto'>${prod.destacados}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2 move-up-btn" title="Subir"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1 move-down-btn" title="Bajar"><i class="bi bi-arrow-down"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2 remove-featured-btn" title="Quitar de destacados"><i class="bi bi-x-lg"></i></button>
                `;
                featuredOrderList.appendChild(item);
            });
            // Listeners para quitar productos
            featuredOrderList.querySelectorAll('.remove-featured-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const parent = btn.closest('.list-group-item');
                    parent.remove();
                });
            });
            // Listeners para mover arriba/abajo
            featuredOrderList.querySelectorAll('.move-up-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const item = btn.closest('.list-group-item');
                    if (item.previousElementSibling) {
                        featuredOrderList.insertBefore(item, item.previousElementSibling);
                    }
                });
            });
            featuredOrderList.querySelectorAll('.move-down-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const item = btn.closest('.list-group-item');
                    if (item.nextElementSibling) {
                        featuredOrderList.insertBefore(item.nextElementSibling, item);
                    }
                });
            });
            // Drag & drop deshabilitado en móvil y desktop
        });
}
    window.openFeaturedOrderModal = openFeaturedOrderModal;

    // Inicializar listeners una sola vez
    document.addEventListener('DOMContentLoaded', function() {
        featuredOrderList = document.getElementById('featuredOrderList');
        featuredOrderModal = new bootstrap.Modal(document.getElementById('featuredOrderModal'));
        document.getElementById('saveFeaturedOrderBtn').addEventListener('click', saveFeaturedOrder);
    });

// Drag & drop deshabilitado. Solo flechas arriba/abajo.

    function saveFeaturedOrder() {
      // Obtener ids y orden de los destacados actuales
      const destacadosArr = Array.from(featuredOrderList.children).map((el, idx) => ({id:el.getAttribute('data-id'), destacados:idx+1}));
      // Obtener todos los ids de productos destacados antes de cambios (para saber cuáles fueron quitados)
      fetch('admin_products.php?action=get_featured_products')
        .then(r => r.json())
        .then(originalData => {
          const originalIds = originalData.map(p => String(p.id));
          const currentIds = destacadosArr.map(p => String(p.id));
          // Los ids que estaban y ya no están deben ir con destacados=0
          const removed = originalIds.filter(id => !currentIds.includes(id)).map(id => ({id:id, destacados:0}));
          // Unir los arrays
          const payload = [...destacadosArr, ...removed];
          fetch('admin_products.php?action=save_featured_order', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
          })
          .then(r=>r.json())
          .then(resp=>{
            if(resp.success){
              featuredOrderModal.hide();
              location.reload();
            }else{
              alert('Error al guardar el orden');
            }
          });
        });
    }
    // Cargar subcategorías al cambiar categoría (alta)
document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    const subcategorySelect = document.getElementById('subcategory_id');
    subcategorySelect.innerHTML = '<option value="">Seleccionar</option>';
    document.getElementById('aromas').innerHTML = '<option disabled>Seleccione una subcategoría</option>';
    if (categoryId) {
        fetch(`get_subcategories.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    subcategorySelect.innerHTML = '<option disabled>No hay subcategorías</option>';
                } else {
                    data.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        subcategorySelect.appendChild(option);
                    });
                }
            });
    }
});

// Cargar aromas según subcategoría seleccionada (alta)
document.getElementById('subcategory_id').addEventListener('change', function() {
    const subcatId = this.value;
    const aromasSelect = document.getElementById('aromas');
    aromasSelect.innerHTML = '';
    if (subcatId) {
        fetch('get_aromas_by_subcat.php?subcat_id=' + subcatId)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    aromasSelect.innerHTML = '<option disabled>No hay aromas asociados</option>';
                } else {
                    data.forEach(aroma => {
                        const option = document.createElement('option');
                        option.value = aroma.id;
                        option.textContent = aroma.nombre;
                        aromasSelect.appendChild(option);
                    });
                }
            });
    } else {
        aromasSelect.innerHTML = '<option disabled>Seleccione una subcategoría</option>';
    }
});

// Modal de edición: cargar subcategorías y aromas dinámicamente
function editProduct(product, images, aromasIds) {
    document.getElementById('edit_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_description').value = product.description;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_price_sale').value = product.price_sale || '';
    document.getElementById('edit_category_id').value = product.category_id;
    document.getElementById('edit_destacados').checked = product.destacados == 1;

    // Cargar subcategorías
    const subcategorySelect = document.getElementById('edit_subcategory_id');
    subcategorySelect.innerHTML = '<option value="">Seleccionar</option>';
    document.getElementById('edit_aromas').innerHTML = '<option disabled>Seleccione una subcategoría</option>';
    if (product.category_id) {
        fetch(`get_subcategories.php?category_id=${product.category_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    subcategorySelect.innerHTML = '<option disabled>No hay subcategorías</option>';
                } else {
                    data.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        if (subcategory.id == product.subcategory_id) {
                            option.selected = true;
                        }
                        subcategorySelect.appendChild(option);
                    });
                    // Cargar aromas después de subcategoría
                    if (product.subcategory_id) {
                        fetch('get_aromas_by_subcat.php?subcat_id=' + product.subcategory_id)
                            .then(response => response.json())
                            .then(data => {
                                const aromasSelect = document.getElementById('edit_aromas');
                                aromasSelect.innerHTML = '';
                                if (data.length === 0) {
                                    aromasSelect.innerHTML = '<option disabled>No hay aromas asociados</option>';
                                } else {
                                    data.forEach(aroma => {
                                        const option = document.createElement('option');
                                        option.value = aroma.id;
                                        option.textContent = aroma.nombre;
                                        option.selected = aromasIds.includes(parseInt(aroma.id));
                                        aromasSelect.appendChild(option);
                                    });
                                }
                            });
                    }
                }
            });
    }

    // Mostrar imágenes actuales
    const imagesContainer = document.getElementById('current_images_container');
    imagesContainer.innerHTML = '';
    if (images && images.length > 0) {
        images.forEach(image => {
            const imageDiv = document.createElement('div');
            imageDiv.className = 'image-container';
            imageDiv.innerHTML = `
                <img src="${image.image_path}" class="image-thumbnail ${image.is_main ? 'main-image' : ''}" 
                     style="width: 80px; height: 80px;">
                <div class="form-check mt-2 text-center">
                    <input class="form-check-input" type="radio" name="main_image" 
                           value="${image.id}" ${image.is_main ? 'checked' : ''}>
                    <label class="form-check-label">Principal</label>
                </div>
                <button type="button" class="delete-image-btn" 
                        onclick="deleteImage(${image.id}, this)">&times;</button>
            `;
            imagesContainer.appendChild(imageDiv);
        });
    } else {
        imagesContainer.innerHTML = '<p class="text-muted">No hay imágenes</p>';
    }

    // Mostrar modal
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// Modal de edición: cargar aromas al cambiar subcategoría
document.getElementById('edit_subcategory_id').addEventListener('change', function() {
    const subcatId = this.value;
    const aromasSelect = document.getElementById('edit_aromas');
    aromasSelect.innerHTML = '';
    if (subcatId) {
        fetch('get_aromas_by_subcat.php?subcat_id=' + subcatId)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    aromasSelect.innerHTML = '<option disabled>No hay aromas asociados</option>';
                } else {
                    data.forEach(aroma => {
                        const option = document.createElement('option');
                        option.value = aroma.id;
                        option.textContent = aroma.nombre;
                        aromasSelect.appendChild(option);
                    });
                }
            });
    } else {
        aromasSelect.innerHTML = '<option disabled>Seleccione una subcategoría</option>';
    }
});

// Eliminar imagen en el modal de edición
function deleteImage(imageId, button) {
    if (confirm('¿Eliminar esta imagen?')) {
        fetch('delete_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `image_id=${imageId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.closest('.image-container').remove();
            } else {
                alert('Error al eliminar la imagen');
            }
        });
    }
}
    </script>
    <!-- Funcion para busqueda de productos -->
    <script>
function normalize(str) {
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
}

document.getElementById('searchAdminProducts').addEventListener('input', function(e) {
    const searchTerm = normalize(e.target.value);
    document.querySelectorAll('#products-table tr').forEach(function(row) {
        const nameCell = row.querySelector('.product-name');
        if (nameCell) {
            const productName = normalize(nameCell.textContent);
            row.style.display = productName.includes(searchTerm) ? '' : 'none';
        }
    });
});
</script>
</body>
<?php require_once 'components/footer.php'; ?>
</html>