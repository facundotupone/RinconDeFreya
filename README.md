# Rincón Freya - Admin E-commerce

Este proyecto es un panel de administración para la tienda Rincón Freya, desarrollado en PHP y Bootstrap 5.

## Características principales
- Gestión de productos, categorías, subcategorías y aromas
- Carga múltiple de imágenes por producto
- Orden y gestión de productos destacados (drag & drop/flechas)
- Edición y eliminación de productos
- Control de stock
- Búsqueda instantánea de productos

## Instalación
1. Clona este repositorio:
   ```
   git clone https://github.com/tuusuario/rincon-freya-admin.git
   ```
2. Copia y configura los archivos de conexión en `includes/config.php` y `includes/db.php` (no incluidos por seguridad).
3. Asegúrate de tener una base de datos MySQL y los datos de acceso correctos.
4. Sube las imágenes de productos a `assets/images/products/` (la carpeta está ignorada en git).

## Seguridad
- **No subas archivos de configuración ni bases de datos reales.**
- El archivo `.gitignore` ya protege los datos sensibles y las imágenes.

## Estructura principal
- `admin_products.php` — Panel principal de productos
- `includes/` — Funciones, conexión y utilidades (sin datos sensibles en el repo)
- `assets/` — CSS, JS y recursos estáticos
- `components/` — Fragmentos reutilizables (footer, nav, head)

## Licencia
Uso privado. Adaptar según necesidades.
