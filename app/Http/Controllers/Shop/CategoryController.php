<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Productos disponibles de una categoría activa.
     */
    public function __invoke(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $products = $category->products()->availableInStore()->getQuery();

        return view('shop.products.index', [
            'title' => $category->name,
            'eyebrow' => 'Categoría',
            'products' => ProductController::sorted($products, $request->query('orden'))->paginate(24)->withQueryString(),
            'category' => $category,
        ]);
    }
}
