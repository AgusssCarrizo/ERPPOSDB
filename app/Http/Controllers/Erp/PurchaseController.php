<?php

namespace App\Http\Controllers\Erp;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Erp\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\Purchasing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['supplier', 'from', 'to']);

        $purchases = Purchase::query()
            ->with('supplier')
            ->withCount('items')
            ->withCount(['items as pending_reviews_count' => fn ($query) => $query->pendingPriceReview()])
            ->when($filters['supplier'] ?? null, fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('date', '<=', $date))
            ->latest('date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('erp.purchases.index', [
            'purchases' => $purchases,
            'filters' => $filters,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'pendingReviews' => PurchaseItem::pendingPriceReview()->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('erp.purchases.create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'cost_price', 'stock']),
            'methods' => PaymentMethod::cases(),
            'selectedSupplier' => $request->integer('supplier') ?: null,
        ]);
    }

    public function store(PurchaseRequest $request, Purchasing $purchasing): RedirectResponse
    {
        $paidNow = (float) $request->input('paid_now');

        $purchase = $purchasing->register(
            $request->safe()->only(['supplier_id', 'date', 'invoice_number', 'tax_amount', 'notes']),
            array_values($request->validated('items')),
            $paidNow > 0 ? ['amount' => $paidNow, 'method' => $request->enum('payment_method', PaymentMethod::class)] : null,
        );

        return redirect()
            ->route('erp.purchases.show', $purchase)
            ->with('success', "Compra {$purchase->number} registrada: el stock ya está actualizado.");
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'items.product', 'payments']);

        return view('erp.purchases.show', [
            'purchase' => $purchase,
            'pendingReviews' => $purchase->items->filter(fn (PurchaseItem $item) => $item->price_reviewed_at === null),
        ]);
    }
}
