<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erp\SupplierRequest;
use App\Models\Supplier;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->withBalance()
            ->withMax('purchases as last_purchase_date', 'date')
            ->when($request->input('q'), fn ($query, $term) => $query->where(fn ($query) => $query
                ->whereLike('name', "%{$term}%")
                ->orWhereLike('cuit', "%{$term}%")
                ->orWhereLike('contact_name', "%{$term}%")))
            ->orderBy('name')
            ->get();

        return view('erp.suppliers.index', [
            'suppliers' => $suppliers,
            'totalDebt' => $suppliers->sum(fn (Supplier $supplier) => max(0, $supplier->balance)),
            'search' => $request->input('q'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('erp.suppliers.create', ['supplier' => new Supplier]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('erp.suppliers.show', $supplier)
            ->with('success', "Proveedor \"{$supplier->name}\" creado.");
    }

    /**
     * Cuenta corriente del proveedor: compras (debe), pagos (haber) y saldo.
     */
    public function show(Supplier $supplier): View
    {
        return view('erp.suppliers.show', [
            'supplier' => $supplier,
            'entries' => $this->statement($supplier),
            'balance' => $supplier->balance,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier): View
    {
        return view('erp.suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('erp.suppliers.show', $supplier)
            ->with('success', 'Cambios guardados.');
    }

    /**
     * Solo se puede borrar un proveedor sin compras ni pagos (para no perder la historia).
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists() || $supplier->payments()->exists()) {
            return back()->with('error', 'No se puede eliminar: el proveedor tiene compras o pagos registrados.');
        }

        $supplier->delete();

        return redirect()
            ->route('erp.suppliers.index')
            ->with('success', "Proveedor \"{$supplier->name}\" eliminado.");
    }

    /**
     * Compras y pagos ordenados por fecha con el saldo acumulado, el más reciente primero.
     *
     * @return Collection<int, array{date: CarbonInterface, kind: string, description: string, url: ?string, debit: float, credit: float, balance: float}>
     */
    private function statement(Supplier $supplier): Collection
    {
        $purchases = $supplier->purchases()->withCount('items')->get()->map(fn ($purchase) => [
            'date' => $purchase->date,
            'created_at' => $purchase->created_at,
            'kind' => 'purchase',
            'description' => "Compra {$purchase->number}".($purchase->invoice_number ? " · Fact. {$purchase->invoice_number}" : '')
                ." · {$purchase->items_count} ".($purchase->items_count === 1 ? 'producto' : 'productos'),
            'url' => route('erp.purchases.show', $purchase),
            'debit' => (float) $purchase->total,
            'credit' => 0.0,
        ]);

        $payments = $supplier->payments()->get()->map(fn ($payment) => [
            'date' => $payment->date,
            'created_at' => $payment->created_at,
            'kind' => 'payment',
            'description' => 'Pago · '.$payment->method->label().($payment->note ? " · {$payment->note}" : ''),
            'url' => null,
            'debit' => 0.0,
            'credit' => (float) $payment->amount,
        ]);

        $balance = 0.0;

        return $purchases->concat($payments)
            ->sortBy([['date', 'asc'], ['created_at', 'asc']])
            ->map(function (array $entry) use (&$balance) {
                $balance = round($balance + $entry['debit'] - $entry['credit'], 2);

                return [...$entry, 'balance' => $balance];
            })
            ->reverse()
            ->values();
    }
}
