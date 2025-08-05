<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid flex-lg-column align-items-stretch">
        <!-- Logo centrado -->
        <div class="d-flex justify-content-center align-items-center w-100 position-relative">
            <a class="navbar-brand mx-auto" href="https://www.rincondefreya.com.ar">
                <img src="assets/images/logo.jpg" alt="Rincón de Freya" height="50" class="d-inline-block align-top">
                
            </a>
            <button class="navbar-toggler position-absolute end-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        
        <!-- Menú de categorías en múltiples líneas -->
        <div class="collapse navbar-collapse w-100" id="navbarNav">
            <ul class="navbar-nav flex-wrap justify-content-center">
                <?php 
                $categories = get_categories();
                foreach($categories as $category): 
                    $subcategories = get_subcategories($category['id']);
                ?>
                <li class="nav-item dropdown mx-1 my-1">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (!empty($category['icon'])): ?>
                            <i class="<?php echo htmlspecialchars($category['icon']); ?>"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                    <?php if (!empty($subcategories)): ?>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="category.php?id=<?php echo $category['id']; ?>">
                                Ver todo en <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach($subcategories as $subcategory): ?>
                        <li>
                            <a class="dropdown-item" href="category.php?id=<?php echo $category['id']; ?>&sub=<?php echo $subcategory['id']; ?>">
                                <?php echo htmlspecialchars($subcategory['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>