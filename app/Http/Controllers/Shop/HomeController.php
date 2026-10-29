<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Portada de la tienda: hero, categorías, "Lo más buscado" (los ❤ del ERP), promoción y beneficios.
     */
    public function __invoke(): View
    {
        $settings = StoreSetting::current();

        // La promoción solo se muestra si el producto se puede comprar (publicado y con stock).
        $promoProduct = $settings->promo_product_id
            ? Product::availableInStore()->find($settings->promo_product_id)
            : null;

        return view('shop.home', [
            'settings' => $settings,
            'categories' => Category::inStore()->ordered()->get(),
            'featured' => Product::availableInStore()->where('is_featured', true)->latest('updated_at')->take(8)->get(),
            'promoProduct' => $promoProduct,
        ]);
    }
}
