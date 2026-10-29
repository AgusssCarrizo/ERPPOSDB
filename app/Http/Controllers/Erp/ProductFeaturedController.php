<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductFeaturedController extends Controller
{
    /**
     * El corazón del listado: marca o desmarca el producto como destacado
     * (sale en "Lo más buscado" de la portada de la tienda).
     */
    public function __invoke(Product $product): JsonResponse
    {
        $product->update(['is_featured' => ! $product->is_featured]);

        return response()->json(['is_featured' => $product->is_featured]);
    }
}
