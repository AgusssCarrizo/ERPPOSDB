<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Erp\CatalogController;
use App\Http\Controllers\Erp\CategoryController;
use App\Http\Controllers\Erp\InventoryController;
use App\Http\Controllers\Erp\ProductController;
use App\Http\Controllers\Erp\ProductFeaturedController;
use App\Http\Controllers\Erp\ProductImportController;
use App\Http\Controllers\Erp\ProductPricingController;
use App\Http\Controllers\Erp\ProductQuickUpdateController;
use App\Http\Controllers\Erp\ProductStoryController;
use App\Http\Controllers\Erp\PurchaseController;
use App\Http\Controllers\Erp\PurchasePriceReviewController;
use App\Http\Controllers\Erp\StoreSettingController;
use App\Http\Controllers\Erp\SupplierController;
use App\Http\Controllers\Erp\SupplierPaymentController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\SaleController;
use App\Http\Controllers\Shop;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tienda');

/*
|--------------------------------------------------------------------------
| Ingreso (ERP y punto de venta). No hay registro: los usuarios se crean con `php artisan erp:usuario`.
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('ingresar', [LoginController::class, 'create'])->name('login');
    Route::post('ingresar', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});
Route::post('salir', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| ERP (backoffice)
|--------------------------------------------------------------------------
*/
Route::prefix('erp')->name('erp.')->middleware('auth')->group(function () {
    // Entrada al ERP: todavía no hay Panel, así que /erp lleva a Productos (sin sesión, primero al login).
    Route::redirect('/', '/erp/productos')->name('home');

    // Antes del resource: si no, "importar" se tomaría como el {product} de productos/{product}.
    Route::get('productos/importar', [ProductImportController::class, 'create'])->name('products.import');
    Route::get('productos/importar/plantilla', [ProductImportController::class, 'template'])->name('products.import.template');
    Route::post('productos/importar/revisar', [ProductImportController::class, 'preview'])->name('products.import.preview');
    Route::post('productos/importar', [ProductImportController::class, 'store'])->name('products.import.store');

    Route::resource('productos', ProductController::class)
        ->parameters(['productos' => 'product'])
        ->names('products');

    Route::resource('categorias', CategoryController::class)
        ->except('show')
        ->parameters(['categorias' => 'category'])
        ->names('categories');

    Route::patch('productos/{product}/precio', ProductPricingController::class)->name('products.pricing');
    Route::patch('productos/{product}/rapido', ProductQuickUpdateController::class)->name('products.quick-update');
    Route::patch('productos/{product}/destacado', ProductFeaturedController::class)->name('products.featured');
    Route::get('productos/{product}/historia', ProductStoryController::class)->name('products.story');

    Route::get('inventario', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventario/ajustar', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('inventario/ajustes', [InventoryController::class, 'store'])->name('inventory.store');

    Route::resource('proveedores', SupplierController::class)
        ->parameters(['proveedores' => 'supplier'])
        ->names('suppliers');
    Route::get('proveedores/{supplier}/pagos/nuevo', [SupplierPaymentController::class, 'create'])->name('suppliers.payments.create');
    Route::post('proveedores/{supplier}/pagos', [SupplierPaymentController::class, 'store'])->name('suppliers.payments.store');

    Route::resource('compras', PurchaseController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['compras' => 'purchase'])
        ->names('purchases');
    Route::post('compras/items/{item}/precio', PurchasePriceReviewController::class)->name('purchases.price-review');

    Route::get('catalogo', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('catalogo/pdf', [CatalogController::class, 'pdf'])->name('catalog.pdf');

    Route::get('tienda', [StoreSettingController::class, 'edit'])->name('store.edit');
    Route::put('tienda', [StoreSettingController::class, 'update'])->name('store.update');
});

/*
|--------------------------------------------------------------------------
| Punto de venta (mostrador)
|--------------------------------------------------------------------------
*/
Route::prefix('pos')->name('pos.')->middleware('auth')->group(function () {
    Route::get('/', PosController::class)->name('index');

    Route::get('ventas', [SaleController::class, 'index'])->name('sales.index');
    Route::post('ventas', [SaleController::class, 'store'])->name('sales.store');
    Route::get('ventas/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('ventas/{sale}/ticket', [SaleController::class, 'ticket'])->name('sales.ticket');
});

/*
|--------------------------------------------------------------------------
| Tienda online (pública)
|--------------------------------------------------------------------------
*/
Route::prefix('tienda')->name('shop.')->group(function () {
    Route::get('/', Shop\HomeController::class)->name('home');
    Route::get('productos', [Shop\ProductController::class, 'index'])->name('products.index');
    Route::get('producto/{product:slug}', [Shop\ProductController::class, 'show'])->name('products.show');
    Route::get('categoria/{category:slug}', Shop\CategoryController::class)->name('categories.show');
});
