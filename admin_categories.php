<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$message = '';
$categories = get_categories();

// Procesar el formulario de categoría y subcategoría
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_category') {
            $name = trim($_POST['name']);
            $icon = trim($_POST['icon']);
            if (!empty($name)) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
                if ($stmt->execute([$name, $icon])) {
                    $message = 'Categoría agregada exitosamente.';
                } else {
                    $message = 'Error al agregar la categoría.';
                }
            }
        } elseif ($_POST['action'] === 'add_subcategory') {
            $name = trim($_POST['subcategory_name']);
            $category_id = intval($_POST['parent_category_id']);
            if (!empty($name) && $category_id > 0) {
                $stmt = $pdo->prepare("INSERT INTO subcategories (name, category_id) VALUES (?, ?)");
                if ($stmt->execute([$name, $category_id])) {
                    $message = 'Subcategoría agregada exitosamente.';
                } else {
                    $message = 'Error al agregar la subcategoría.';
                }
            }
        } elseif ($_POST['action'] === 'delete_category' && isset($_POST['id'])) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$_POST['id']])) {
                $message = 'Categoría eliminada exitosamente.';
            } else {
                $message = 'Error al eliminar la categoría.';
            }
        } elseif ($_POST['action'] === 'delete_subcategory' && isset($_POST['id'])) {
            $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
            if ($stmt->execute([$_POST['id']])) {
                $message = 'Subcategoría eliminada exitosamente.';
            } else {
                $message = 'Error al eliminar la subcategoría.';
            }
        }
    }
}

// Obtener todas las categorías y subcategorías
$categories = get_categories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Categorías - Rincón Freya</title>
    <link rel="icon" href="./assets/images/favicon.ico" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
    /* Optimización móvil para formularios de categorías y subcategorías */
    @media (max-width: 767px) {
        .row > .col-md-6 {
            width: 100%;
            max-width: 100%;
            flex: 0 0 100%;
        }
        .card.shadow-sm.mb-4 {
            margin-bottom: 1.5rem !important;
        }
        .card-body form {
            padding: 0;
        }
        .card-body .form-control, .card-body .form-select {
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
        }
        .card-header {
            text-align: center;
        }
        .container {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        h1, h2, h5 {
            text-align: center;
        }
    }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Admin Rincon de Freya</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="adminNavbar">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_products.php' ? 'active' : ''; ?>" href="admin_products.php">Productos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_categories.php' ? 'active' : ''; ?>" href="admin_categories.php">Categorías</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_aromas.php' ? 'active' : ''; ?>" href="admin_aromas.php">Aromas</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
    <div class="container my-4">
        <h1>Administrar Categorías</h1>
        
        <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h2 class="h5 mb-0">Agregar Nueva Categoría</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="add_category">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre de la Categoría</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="icon" class="form-label">Ícono (clase de Bootstrap Icons)</label>
                                <input type="text" class="form-control" id="icon" name="icon" placeholder="bi-tag">
                            </div>
                            <button type="submit" class="btn btn-primary mb-3">Agregar Categoría</button>
                        </form>
                        <hr>
                        <h2 class="h5 mb-3">Agregar Nueva Subcategoría</h2>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="add_subcategory">
                            <div class="mb-3">
                                <label for="parent_category_id" class="form-label">Categoría Padre</label>
                                <select class="form-control" id="parent_category_id" name="parent_category_id" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="subcategory_name" class="form-label">Nombre de la Subcategoría</label>
                                <input type="text" class="form-control" id="subcategory_name" name="subcategory_name" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Agregar Subcategoría</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <h2>Categorías y Subcategorías Existentes</h2>
                <div class="list-group">
                    <?php foreach ($categories as $category): 
                        $subcategories = get_subcategories($category['id']);
                    ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php if (!empty($category['icon'])): ?>
                                <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                            </div>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" 
                                        onclick="return confirm('¿Estás seguro de eliminar esta categoría y todas sus subcategorías?')">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                        <?php if (!empty($subcategories)): ?>
                        <div class="ms-4 mt-2">
                            <?php foreach ($subcategories as $subcategory): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?php echo htmlspecialchars($subcategory['name']); ?></span>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete_subcategory">
                                    <input type="hidden" name="id" value="<?php echo $subcategory['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('¿Estás seguro de eliminar esta subcategoría?')">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php require_once 'components/footer.php'; ?>
</html>