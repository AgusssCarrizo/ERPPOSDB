<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class Purchasing
{
    public function __construct(private readonly Inventory $inventory) {}

    /**
     * Registra mercadería recibida, en una sola transacción:
     *  - crea la compra y sus ítems,
     *  - suma el stock (movimiento "Compra" en Inventario),
     *  - actualiza el costo de cada producto (el precio de venta NO: queda para revisar),
     *  - si se pagó algo en el momento, lo descuenta de la deuda con el proveedor.
     *
     * @param  array{supplier_id: int, date: string, invoice_number?: ?string, tax_amount?: float|string|null, notes?: ?string}  $data
     * @param  list<array{product_id: int, quantity: int, unit_cost: float|string}>  $items
     * @param  array{amount: float|string, method: PaymentMethod}|null  $payment
     */
    public function register(array $data, array $items, ?array $payment = null): Purchase
    {
        return DB::transaction(function () use ($data, $items, $payment) {
            $subtotal = round(array_sum(array_map(fn ($item) => $item['quantity'] * $item['unit_cost'], $items)), 2);
            $taxAmount = round((float) ($data['tax_amount'] ?? 0), 2);

            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'date' => $data['date'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
            ]);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $previousCost = (float) $product->cost_price;
                $unitCost = round((float) $item['unit_cost'], 2);

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $unitCost,
                    'previous_cost' => $previousCost,
                    'subtotal' => round($item['quantity'] * $unitCost, 2),
                    // Si el costo no cambió, no hay precio que revisar.
                    'price_reviewed_at' => $previousCost === $unitCost ? now() : null,
                ]);

                $this->inventory->record($product, StockMovementType::Purchase, (int) $item['quantity'], [
                    'unit_cost' => $unitCost,
                    'reference' => $purchase,
                    'note' => trim('Compra '.$purchase->number.' '.($purchase->invoice_number ? "· Fact. {$purchase->invoice_number}" : '')),
                ]);

                $product->update(['cost_price' => $unitCost]);
            }

            if ($payment && (float) $payment['amount'] > 0) {
                $purchase->payments()->create([
                    'supplier_id' => $purchase->supplier_id,
                    'date' => $purchase->date,
                    'amount' => $payment['amount'],
                    'method' => $payment['method'],
                    'note' => "Pago con la compra {$purchase->number}",
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Pago a cuenta de la deuda con un proveedor.
     */
    public function pay(Supplier $supplier, float $amount, PaymentMethod $method, string $date, ?string $note = null): void
    {
        $supplier->payments()->create(compact('amount', 'method', 'date', 'note'));
    }
}
