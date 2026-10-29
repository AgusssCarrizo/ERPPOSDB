<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductQuickUpdateController extends Controller
{
    /**
     * Edición rápida desde el listado, sin entrar a editar: categoría (desplegable)
     * y publicado en la tienda online (pastilla "Web").
     */
    public function __invoke(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'is_published' => ['sometimes', 'required', 'boolean'],
        ], [
            'exists' => 'La categoría elegida no existe.',
            'boolean' => 'Valor inválido.',
        ]);

        $product->update($data);

        return response()->json([
            'category_id' => $product->category_id,
            'is_published' => $product->is_published,
        ]);
    }
}
