# ERP Bazar — contexto del proyecto

ERP + POS + E-commerce para **Deco Bazar** (@decobazar.db · WhatsApp +54 3388 458046), un bazar con **un solo local**.
Laravel 13 · PHP 8.5 · SQLite · Blade + Tailwind 4 + Alpine.js · dompdf · GD. Windows (PowerShell + Git Bash). No es repo git todavía.

## Cómo trabajar con el usuario

- Escribe en español rioplatense: **responder en español**. Toda la UI, mensajes de validación y comentarios del código van en español.
- Está **aprendiendo** mientras construye su propio negocio: código legible, comentarios que expliquen el *por qué* (este proyecto sí usa comentarios inline breves en español; mantener ese estilo).
- Pide cosas cortas e incrementales. Preguntar solo decisiones de negocio reales; el resto, decidir y avisar.
- Deja archivos de referencia (diseños de Canva, capturas de bugs) en **`helper/`** (raíz). Si dice "te subí una foto/imagen", mirar ahí (ordenar por fecha). `helper/` no lo usa la app.
- **Verificar lo visual uno mismo** antes de reportar: renderizar imágenes/PDFs y mirarlos (el Read de PDF completo funciona; por página no, falta pdftoppm). Ya pasó que el precio del catálogo salía corrido y no lo vi.

## Reglas de negocio (decididas por el usuario — no volver a preguntar)

- **Un local, sin depósito**: el stock es un único número por producto.
- **El stock nunca se edita a mano** después de crear el producto. Solo cambia vía `App\Services\Inventory` (deja un `StockMovement`). Al crear un producto, su stock se registra como movimiento "Stock inicial" (evento `created` en `Product`).
- **Margen = sobre el costo** (costo 1000 + 50% = 1500). Es editable: margen → recalcula precio; precio → recalcula margen; costo → mantiene margen.
- **Precio de venta se guarda sin IVA**; precio final = venta × (1 + IVA). IVA libre 0–100 (sugeridos 0 / 10,5 / 21 / 27).
- **Compras**: actualizan stock y **costo** del producto, pero **NUNCA el precio de venta** ("lo reviso yo"). Si el costo cambió, la compra muestra "Revisá los precios" (sugerido = mantiene el margen anterior; Aplicar / Dejar igual).
- **Proveedores**: cuenta corriente con deuda (compras suman, pagos restan). No se borra un proveedor con historia.
- **Formato de precio en piezas de marketing** (historia, catálogo): `45,000` (coma de miles, sin $) → helper `price_tag()`. En la app: `money()` → `$ 1.234,56`.
- **POS**: cobra en Efectivo, Débito, Crédito y Mercado Pago (solo se registra el medio; sin integración con la API de MP). Pago dividido hasta 4 partes; efectivo con "paga con" y vuelto. Precio = final con IVA, lo recalcula el servidor. Descuento por venta ($ o %). No se vende sin stock. Ticket no fiscal.
- **Tienda online**: lista productos **publicados + activos + con stock** de categorías activas (o sin categoría) → `Product::availableInStore()`. La ficha se ve aunque esté agotada (`publishedInStore()`). "Lo más buscado" = productos con ❤ (`is_featured`). OFERTA = `compare_price` (precio anterior, **final con IVA**) mayor al precio final. Todavía no hay carrito: se compra por WhatsApp (mensaje prearmado). Favoritos de los visitantes en localStorage.
- **Catálogo PDF**: A4, solo productos **activos con stock** de categorías activas, **3 por página** (foto, nombre, precio con IVA). Cada categoría tiene **foto de portada obligatoria**. Dejar abierto "Agregar sección" para **Descuentos** (futuro).

## Mapa del código

| Módulo | Dónde |
|---|---|
| Productos (CRUD, filtros, edición rápida margen/IVA con doble click; categoría con desplegable y "Web" con un click) | `Erp/ProductController`, `Erp/ProductPricingController` (PATCH JSON), `Erp/ProductQuickUpdateController` (categoría / publicado), `Product::pricingSummary()`, Alpine `productForm` / `productRow` / `quickField` |
| Importar productos (Excel .xlsx / CSV, revisar → confirmar) | `Erp/ProductImportController`, `Services/SpreadsheetReader` (lee xlsx con ZipArchive, sin paquetes), `Services/ProductImport` (alias de columnas, formatos de número, SKU automático `DB-00001`, crea categorías faltantes). Solo crea productos nuevos |
| Categorías (foto de portada, orden `position`) | `Erp/CategoryController`, `Category::ordered()` |
| Inventario (movimientos, ajustes sumar/restar/conteo) | `Services/Inventory.php`, `Models/StockMovement`, `Enums/StockMovementType`, `Enums/AdjustmentReason`, `Erp/InventoryController` |
| Compras + revisión de precios | `Services/Purchasing.php`, `Erp/PurchaseController`, `Erp/PurchasePriceReviewController`, `PurchaseItem::suggestedPrice` |
| Proveedores + pagos | `Erp/SupplierController` (cuenta corriente en `statement()`), `Erp/SupplierPaymentController`, `Supplier::withBalance()`, `Enums/PaymentMethod` |
| POS (venta, cobro, ventas, ticket) | `Services/Selling.php`, `Pos/PosController` (pantalla), `Pos/SaleController` (store JSON, listado, detalle, ticket 80 mm), `Requests/Pos/SaleRequest`, `Enums/SalePaymentMethod`, modelos `Sale`/`SaleItem`/`SalePayment`, Alpine `posTerminal` |
| Historia de Instagram 1080×1920 | `Services/ProductStoryImage.php` (GD) sobre `resources/story/base.png` |
| Precio dentro del marco (historia y catálogo) | `Services/PriceTagRenderer.php` |
| Catálogo PDF por secciones | `app/Catalog/` (`Section` interfaz, `SectionType` enum, `CatalogBuilder`, `CatalogImages`, `Sections/*`), vistas `resources/views/catalog/` |
| Tienda online (pública, `/tienda`) | `Shop/HomeController`, `Shop/ProductController` (listado/búsqueda/ficha, `sorted()`), `Shop/CategoryController`; vistas `shop/`, layout `components/layouts/shop`, `x-shop.product-card`. Diseño: `helper/ecommerce-1.png` y `ecommerce-2.png` (Playfair + Jost, tokens `cream`/`sand`/`ink` en `app.css`, clases `shop-btn`, `shop-eyebrow`) |
| Configuración de la tienda (ERP → Tienda online) | `Erp/StoreSettingController`, `StoreSetting::current()` (una sola fila, `DEFAULTS` = textos del diseño): anuncio, portada, promoción, beneficios, contacto. Corazón ❤ del listado = `is_featured` (`Erp/ProductFeaturedController`, Alpine `featuredToggle`) |
| Helpers globales | `app/helpers.php`: `money()`, `percent()`, `price_tag()`, `shop_price()` (`$12.500`) |
| UI compartida | `components/layouts/app.blade.php` (menú; `route => null` = "Pronto"), `x-icon`, `x-form.field`, `x-stat-card`, `x-stock-badge`, `x-supplier-balance`; clases en `resources/css/app.css` (`btn-primary`, `form-input`, `card`, `toggle`…) |

Rutas bajo `/erp` (nombres `erp.*`), URLs en español (`productos`, `categorias`, `inventario`, `compras`, `proveedores`, `catalogo`, `tienda`). POS bajo `/pos` (nombres `pos.*`, `ventas`). Tienda bajo `/tienda` (nombres `shop.*`: `productos`, `producto/{slug}`, `categoria/{slug}`).

## Convenciones

- Modelos con atributos de Laravel 13: `#[Fillable([...])]`, `#[Scope]`; casts en `casts()`; enums para tipos fijos (con `label()`).
- Slugs automáticos con el trait `Models/Concerns/HasSlug`.
- Validación en FormRequests con mensajes y atributos en español.
- Movimientos de stock, compras y pagos son **inmutables** (se corrigen con otro movimiento). Pendiente: "anular compra".
- Imágenes subidas en disco `public` (`products/`, `categories/`); `php artisan storage:link` ya hecho.
- Tests: feature tests con `RefreshDatabase` y `$this->withoutVite()`; correr `php artisan test` + `vendor/bin/pint` + `npm run build` al terminar.

## Diseño de marca (de su plantilla de Canva `helper/canva.png`)

- Título: **Playfair Display** marrón `#58351B`, mayúsculas con letter-spacing (historia: 46pt, tracking 0,13). Playfair tiene **números de estilo antiguo** → los dígitos van en **Bodoni Moda** (`Typography::title()` en el PDF, `titleFontFor()` en GD).
- Precio: **Hind SemiBold** negro dentro del marco ornamental. Fondo blanco, ramitas beige, pie con logo DB + Instagram + WhatsApp.
- Recortes en `resources/catalog/*.png` (ramitas, marco, pie, logo) y plantilla limpia `resources/story/base.png`. Fuentes OFL en `resources/fonts/` con sus licencias.
- El logo disponible es chico (~80 px): pedir uno en alta si hace falta en grande.

## Trampas ya resueltas (no repetir)

- **GD en Windows** necesita rutas **absolutas** de fuentes (`resource_path()`/`realpath`), si no "Could not find/open font".
- **GD + fuentes variables**: dibuja solo el peso por defecto → para negrita usar TTF estáticas (por eso Hind-SemiBold).
- **dompdf**: solo CSS 2.1 (sin flex/grid) → posicionar en mm con `position:absolute`; centrar vertical con `<table><td style="vertical-align:middle">`. Hace falta `<meta charset=utf-8>` (si no, tildes = "?") y la carpeta `storage/fonts`. Imágenes como data URI achicadas (`CatalogImages`).
- **dompdf + Hind**: ubica la línea base con ascent/descent de la fuente (muy altos por escrituras indias) → texto corrido hacia abajo. Por eso el precio se dibuja como imagen con GD (`PriceTagRenderer`).
- **Tailwind 4**: no se puede `@apply` una clase propia (ej. `form-input`) → usar ambas clases en el HTML.
- **Heredocs de Git Bash** fallan con caracteres como `«»` o `'\\'`: escribir archivos con la herramienta Write.
- Tinker desde PowerShell: escapar `$` con backtick.
- **SQLite + división**: `tax_rate / 100` es división entera (da 0) → usar `100.0` en SQL crudo.
- **`public/hot` viejo** (Vite que no se cerró bien) rompe `@fonts`/`@vite` y los tests ("Font alias not defined"): borrarlo junto con `public/fonts-manifest.dev.json`.
- Fuentes por layout: ERP `@fonts('instrument-sans')`, tienda `@fonts(['playfair-display', 'jost'])` (definidas en `vite.config.js`).

## Comandos

```bash
composer run dev                    # servidor + Vite
php artisan migrate:fresh --seed    # base de ejemplo (borra todo, incluidos proveedores/compras cargados)
php artisan test                    # 76 tests al 2026-10-29
vendor/bin/pint                     # estilo
npm run build                       # assets
```

## Estado al 2026-10-29 y próximos pasos

Hecho: Productos, Categorías, Inventario, Compras, Proveedores (con deuda), Historias de Instagram, Catálogo PDF, POS (vender + listado de ventas + ticket), Tienda online (portada, categorías, búsqueda, ficha con compra por WhatsApp) + su configuración en el ERP.
Base real: 73 productos importados (Hudson, etc.) **sin categoría, sin foto y sin publicar** → la tienda arranca vacía hasta que se publiquen.
Tienda pendiente: carrito + checkout → Pedidos en el ERP (decidir cobro: WhatsApp / Mercado Pago / transferencia, y envíos), páginas de políticas (envíos, cambios), newsletter, cuentas de clientes.
Datos de prueba cargados a mano: producto 15 "Set de cubiertos x24" con foto demo, proveedor "Hudson SA" con compra C-000001 (precio pendiente de revisión). La categoría "Cocina" tiene como foto una historia vertical (se recorta mal en el catálogo; se le avisó).
Ideas pendientes: anular compra, anular venta / devolución, Caja (apertura/cierre/arqueo), cobro integrado con Mercado Pago (QR/Point), recargo por cuotas, tienda online, sección Descuentos del catálogo, Marcas, Clientes, login (hoy no hay auth), Panel.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
