<?php

namespace App\Http\Controllers\Erp;

use App\Enums\AdjustmentReason;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Erp\StockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Inventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * Resumen del inventario y listado de movimientos.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'product', 'type', 'from', 'to']);

        $movements = StockMovement::query()
            ->with('product')
            ->filter($filters)
            ->latest()
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $inStock = Product::where('stock', '>', 0);

        return view('erp.inventory.index', [
            'movements' => $movements,
            'filters' => $filters,
            'types' => StockMovementType::cases(),
            'product' => isset($filters['product']) ? Product::withTrashed()->find($filters['product']) : null,
            'stats' => [
                'units' => (int) (clone $inStock)->sum('stock'),
                'cost_value' => (float) (clone $inStock)->sum(DB::raw('cost_price * stock')),
                'sale_value' => (float) (clone $inStock)->sum(DB::raw('sale_price * (1 + tax_rate / 100.0) * stock')),
                'low_stock' => Product::lowStock()->count(),
                'out_of_stock' => Product::outOfStock()->count(),
            ],
        ]);
    }

    /**
     * Formulario de ajuste (sumar, restar o conteo).
     */
    public function create(Request $request): View
    {
        return view('erp.inventory.adjust', [
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'stock']),
            'selected' => $request->integer('product') ?: null,
            'reasons' => AdjustmentReason::cases(),
            'modes' => StockAdjustmentRequest::MODES,
            'return' => $request->query('return'),
        ]);
    }

    public function store(StockAdjustmentRequest $request, Inventory $inventory): RedirectResponse
    {
        $product = Product::findOrFail($request->integer('product_id'));
        $quantity = $request->integer('quantity');
        $note = $request->input('note');

        $movement = match ($request->input('mode')) {
            'add' => $inventory->adjust($product, $quantity, $request->enum('reason', AdjustmentReason::class), $note),
            'remove' => $inventory->adjust($product, -$quantity, $request->enum('reason', AdjustmentReason::class), $note),
            'count' => $inventory->count($product, $quantity, $note),
        };

        $message = $movement
            ? "Stock de \"{$product->name}\" actualizado: ahora hay {$product->stock}."
            : "El conteo de \"{$product->name}\" coincide con el sistema ({$product->stock}). No hizo falta ajustar.";

        $redirect = $request->input('return') === 'inventory'
            ? redirect()->route('erp.inventory.index')
            : redirect()->route('erp.products.show', $product);

        return $redirect->with('success', $message);
    }
}
