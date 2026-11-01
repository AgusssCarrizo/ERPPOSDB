<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Órdenes del listado. El precio final depende del IVA, así que se ordena por la cuenta completa
     * (con 100.0: en SQLite 21 / 100 da 0 por ser división entera).
     */
    public const SORTS = [
        'recent' => 'Más nuevos',
        'price_asc' => 'Menor precio',
        'price_desc' => 'Mayor precio',
        'name' => 'Nombre (A–Z)',
    ];

    /**
     * Todos los productos disponibles, o los que coinciden con la búsqueda del encabezado.
     */
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q'));

        $products = Product::availableInStore()
            ->when($term !== '', fn (Builder $query) => $query->whereLike('name', "%{$term}%"));

        return view('shop.products.index', [
            'title' => $term !== '' ? "Resultados para “{$term}”" : 'Todos los productos',
            'eyebrow' => $term !== '' ? 'Búsqueda' : 'Catálogo',
            'products' => self::sorted($products, $request->query('orden'))->paginate(24)->withQueryString(),
            'category' => null,
        ]);
    }

    /**
     * Ficha del producto. Se ve aunque no tenga stock (el link pudo haberse compartido antes de agotarse).
     */
    public function show(Product $product): View
    {
        abort_unless(Product::publishedInStore()->whereKey($product->id)->exists(), 404);

        $settings = StoreSetting::current();

        return view('shop.products.show', [
            'product' => $product->load('category'),
            'settings' => $settings,
            'whatsappUrl' => $settings->whatsappUrl("¡Hola! Quiero comprar: {$product->name} (".shop_price($product->final_price).') '.route('shop.products.show', $product)),
            'related' => Product::availableInStore()
                ->whereKeyNot($product->id)
                ->when($product->category_id, fn (Builder $query, int $id) => $query->where('category_id', $id))
                ->inRandomOrder()
                ->take(4)
                ->get(),
        ]);
    }

    /**
     * Aplica el orden elegido en el listado (lo usan también las páginas de categoría).
     */
    public static function sorted(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderByRaw('sale_price * (1 + tax_rate / 100.0) asc'),
            'price_desc' => $query->orderByRaw('sale_price * (1 + tax_rate / 100.0) desc'),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };
    }
}
