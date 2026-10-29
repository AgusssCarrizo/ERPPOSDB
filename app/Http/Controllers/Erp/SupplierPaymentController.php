<?php

namespace App\Http\Controllers\Erp;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Erp\SupplierPaymentRequest;
use App\Models\Supplier;
use App\Services\Purchasing;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierPaymentController extends Controller
{
    public function create(Supplier $supplier): View
    {
        return view('erp.suppliers.payment', [
            'supplier' => $supplier,
            'balance' => $supplier->balance,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function store(SupplierPaymentRequest $request, Supplier $supplier, Purchasing $purchasing): RedirectResponse
    {
        $purchasing->pay(
            $supplier,
            (float) $request->validated('amount'),
            $request->enum('method', PaymentMethod::class),
            $request->validated('date'),
            $request->validated('note'),
        );

        return redirect()
            ->route('erp.suppliers.show', $supplier)
            ->with('success', 'Pago de '.money($request->validated('amount')).' registrado.');
    }
}
