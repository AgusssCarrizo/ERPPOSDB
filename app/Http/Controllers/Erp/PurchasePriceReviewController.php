<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\PurchaseItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PurchasePriceReviewController extends Controller
{
    /**
     * Revisión del precio de venta después de un cambio de costo:
     * "apply" guarda el precio nuevo, "keep" deja el precio como estaba.
     */
    public function __invoke(Request $request, PurchaseItem $item): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:apply,keep'],
            'sale_price' => ['required_if:action,apply', 'nullable', 'numeric', 'min:0'],
        ], [
            'sale_price.required_if' => 'Poné el precio nuevo.',
            'numeric' => 'El precio tiene que ser un número.',
            'min' => 'El precio no puede ser negativo.',
        ]);

        $product = $item->product;

        if ($data['action'] === 'apply') {
            $product->update(['sale_price' => $data['sale_price']]);
        }

        $item->update(['price_reviewed_at' => now()]);

        return back()->with('success', $data['action'] === 'apply'
            ? "Precio de \"{$product->name}\" actualizado a ".money($product->sale_price).' + IVA.'
            : "\"{$product->name}\" mantiene su precio de ".money($product->sale_price).' + IVA.');
    }
}
