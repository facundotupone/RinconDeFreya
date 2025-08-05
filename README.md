
# Rincón Freya (www.rincondefreya.com.ar) - Panel de Administración E-commerce

> Proyecto profesional desarrollado por Facundo Tupone

## Descripción
Rincón Freya es un panel de administración para e-commerce, pensado para la gestión eficiente de productos, categorías y destacados. El sistema está diseñado para ser robusto, seguro y fácil de usar, ideal para empresas que buscan una solución adaptable y escalable.

## Funcionalidades principales
- ABM de productos, categorías, subcategorías y aromas
- Gestión de productos destacados con orden personalizado (flechas móviles)
- Edición y eliminación de productos con confirmación
- Carga múltiple de imágenes por producto
- Control de stock y visualización rápida
- Búsqueda instantánea y filtros avanzados
- Panel responsive y mobile-friendly

## Tecnologías utilizadas
- **Backend:** PHP 7+, MySQL
- **Frontend:** Bootstrap 5, JavaScript (AJAX), Bootstrap Icons
- **Estructura:** Arquitectura modular, componentes reutilizables

## Instalación y uso
1. Clona el repositorio:
   ```
   git clone https://github.com/facundotupone/RinconDeFreya.git
   ```
2. Configura la base de datos MySQL y los archivos `includes/config.php` y `includes/db.php` (no incluidos por seguridad).
3. Sube tus imágenes de productos a `assets/images/products/`.
4. Accede a `admin_products.php` para comenzar a gestionar el catálogo.

> **Nota:** El archivo `.gitignore` protege datos sensibles y recursos privados. El repositorio es seguro para compartir como portfolio.

## Estructura del proyecto
- `admin_products.php` — Panel principal de administración
- `includes/` — Funciones, conexión y utilidades (sin datos sensibles)
- `assets/` — CSS, JS y recursos estáticos
- `components/` — Fragmentos reutilizables (footer, nav, head)

## Sobre el autor
**Facundo Tupone**  
Desarrollador Full Stack  
[LinkedIn](https://www.linkedin.com/in/facundotupone)  
[GitHub](https://github.com/facundotupone)

## Licencia
Uso privado y demostrativo. Adaptable a proyectos empresariales.
