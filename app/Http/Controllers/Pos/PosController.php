<?php

namespace App\Http\Controllers\Pos;

use App\Enums\SalePaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

/**
 * Pantalla de venta del mostrador.
 */
class PosController extends Controller
{
    public function __invoke(): View
    {
        // Se cargan todos los productos activos de una vez: la búsqueda en pantalla es instantánea
        // y funciona igual con el lector de código de barras.
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'sku', 'barcode', 'sale_price', 'tax_rate', 'stock', 'image_path'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'price' => $product->final_price,
                'stock' => $product->stock,
                'image' => $product->image_url,
            ]);

        return view('pos.index', [
            'products' => $products,
            'categories' => Category::ordered()->whereIn('id', $products->pluck('category_id'))->get(['id', 'name']),
            'methods' => SalePaymentMethod::cases(),
        ]);
    }
}
