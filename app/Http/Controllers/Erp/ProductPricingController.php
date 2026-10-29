<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductPricingController extends Controller
{
    /**
     * Edición rápida desde el listado (doble click): margen y/o IVA.
     * Cambiar el margen recalcula el precio de venta a partir del costo.
     */
    public function __invoke(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'margin' => ['sometimes', 'required', 'numeric', 'min:-100', 'max:10000'],
            'tax_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
        ], [
            'required' => 'Ingresá un valor.',
            'numeric' => 'Tiene que ser un número.',
            'margin.min' => 'El margen no puede ser menor a -100%.',
            'margin.max' => 'El margen es demasiado alto.',
            'tax_rate.min' => 'El IVA no puede ser negativo.',
            'tax_rate.max' => 'El IVA no puede ser mayor a 100%.',
        ]);

        if (array_key_exists('margin', $data)) {
            if ($product->cost_price <= 0) {
                throw ValidationException::withMessages([
                    'margin' => 'Cargá el costo del producto para usar el margen.',
                ]);
            }

            $product->sale_price = round($product->cost_price * (1 + $data['margin'] / 100), 2);
        }

        if (array_key_exists('tax_rate', $data)) {
            $product->tax_rate = $data['tax_rate'];
        }

        $product->save();

        return response()->json($product->pricingSummary());
    }
}
