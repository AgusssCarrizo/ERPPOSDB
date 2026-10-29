<?php

namespace App\Services;

use App\Enums\SalePaymentMethod;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Selling
{
    public function __construct(private readonly Inventory $inventory) {}

    /**
     * Registra una venta del mostrador, en una sola transacción:
     *  - toma el precio final (con IVA) de cada producto desde la base, no desde la pantalla,
     *  - crea la venta, sus ítems y sus pagos,
     *  - descuenta el stock (movimiento "Venta" en Inventario).
     *
     * Los pagos tienen que sumar exactamente el total; el vuelto se registra aparte con "tendered".
     *
     * @param  list<array{product_id: int, quantity: int}>  $items
     * @param  list<array{method: SalePaymentMethod, amount: float|string, tendered?: float|string|null}>  $payments
     */
    public function register(array $items, array $payments, float $discount = 0, ?string $notes = null): Sale
    {
        return DB::transaction(function () use ($items, $payments, $discount, $notes) {
            $products = Product::query()->findMany(array_column($items, 'product_id'))->keyBy('id');

            $lines = array_map(function (array $item) use ($products) {
                $product = $products[$item['product_id']];

                return [
                    'product' => $product,
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => $product->final_price,
                    'subtotal' => round($item['quantity'] * $product->final_price, 2),
                ];
            }, $items);

            $subtotal = round(array_sum(array_column($lines, 'subtotal')), 2);
            $discount = round($discount, 2);
            $total = round($subtotal - $discount, 2);
            $paid = round(array_sum(array_map(fn ($payment) => (float) $payment['amount'], $payments)), 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages(['discount' => 'El descuento no puede superar el total ('.money($subtotal).').']);
            }

            // Si no coincide, lo más probable es que un precio haya cambiado mientras se armaba la venta.
            if (abs($paid - $total) > 0.009) {
                throw ValidationException::withMessages([
                    'payments' => 'Los pagos suman '.money($paid).' y el total es '.money($total).'. Si cambió algún precio, recargá la pantalla.',
                ]);
            }

            $sale = Sale::create([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'tax_rate' => $line['product']->tax_rate,
                    'unit_cost' => $line['product']->cost_price,
                    'subtotal' => $line['subtotal'],
                ]);

                $this->inventory->record($line['product'], StockMovementType::Sale, -$line['quantity'], [
                    'reference' => $sale,
                    'note' => "Venta {$sale->number}",
                ]);
            }

            foreach ($payments as $payment) {
                $sale->payments()->create([
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    // El "paga con" solo tiene sentido en efectivo.
                    'tendered' => $payment['method']->givesChange() ? ($payment['tendered'] ?? null) : null,
                ]);
            }

            return $sale;
        });
    }
}
