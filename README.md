# ERP Bazar

ERP + POS + E-commerce para un bazar, hecho con Laravel 13, Blade, Tailwind CSS 4 y Alpine.js.

## Levantar el proyecto

```bash
composer install
npm install
cp .env.example .env        # solo la primera vez
php artisan key:generate    # solo la primera vez
php artisan migrate --seed  # crea las tablas y carga productos de ejemplo
php artisan storage:link    # para que se vean las imágenes subidas
composer run dev            # servidor + Vite con recarga en vivo
```

Abrir http://localhost:8000. Para reiniciar la base con datos de ejemplo: `php artisan migrate:fresh --seed`.
Tests: `php artisan test`.

## Módulos

| Módulo     | Prefijo URL | Estado                    |
|------------|-------------|---------------------------|
| ERP        | `/erp`      | Productos ✅ · Categorías ✅ · Inventario ✅ · Compras ✅ · Proveedores ✅ · Historias de Instagram ✅ · Catálogo PDF ✅ |
| POS        | `/pos`      | Pendiente                 |
| E-commerce | `/tienda`   | Pendiente                 |

Los tres módulos comparten la misma tabla `products`: el ERP maneja costo y stock, el POS usa
el código de barras y el flag `is_active`, y la tienda usa `slug`, descripción, imagen e `is_published`.

## Dónde está cada cosa

```
app/
  Http/Controllers/Erp/ProductController.php   CRUD de productos
  Http/Requests/Erp/ProductRequest.php         Validación (mensajes en español)
  Models/Product.php                           Scopes de filtros, margen, precio final, estado de stock
  Models/Category.php, Brand.php
  helpers.php                                  money(), percent(), price_tag()
  Services/Inventory.php                       ÚNICO lugar que cambia el stock (deja un StockMovement)
  Services/Purchasing.php                      Registra compras: stock + costo + deuda con el proveedor
  Services/ProductStoryImage.php               Historia de Instagram 1080x1920 (GD)
  Services/PriceTagRenderer.php                Marco con el precio (historia y catálogo)
  Enums/                                       Tipos de movimiento, motivos de ajuste, medios de pago
  Catalog/                                     Catálogo PDF armado por secciones (ver Section.php
                                               para sumar una sección nueva, ej. descuentos)
database/
  migrations/                                  categories, brands, products
  seeders/CatalogSeeder.php                    Catálogo de ejemplo
resources/
  views/components/layouts/app.blade.php       Layout con menú lateral
  views/components/                            icon, stat-card, stock-badge, form/field
  views/erp/products/                          index, create, edit, show, _form
  views/catalog/                               Páginas del PDF (dompdf: CSS 2.1, posiciones en mm)
  story/base.png, catalog/*.png                Piezas del diseño de Canva (plantilla, ramitas, marco, pie)
  fonts/                                       Fuentes libres (OFL) usadas en historias y catálogo
  css/app.css                                  Clases reutilizables (btn-primary, form-input, card…)
  js/app.js                                    Alpine: cálculo de margen y vista previa de imagen
```
