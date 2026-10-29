<?php

namespace App\Services;

use App\Enums\AdjustmentReason;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Único lugar donde cambia el stock de un producto (salvo el stock inicial al crearlo).
 * Cada cambio deja un StockMovement y actualiza products.stock en la misma transacción.
 */
class Inventory
{
    /**
     * Registra un movimiento. $quantity positivo suma, negativo resta.
     *
     * @param  array{reason?: AdjustmentReason, note?: string|null, unit_cost?: float|string|null, reference?: Model}  $details
     */
    public function record(Product $product, StockMovementType $type, int $quantity, array $details = []): StockMovement
    {
        return DB::transaction(function () use ($product, $type, $quantity, $details) {
            // Relee el producto bloqueándolo, por si dos movimientos llegan a la vez.
            $locked = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $stockAfter = $locked->stock + $quantity;

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "No alcanza el stock: hay {$locked->stock} y querés sacar ".abs($quantity).'.',
                ]);
            }

            $locked->update(['stock' => $stockAfter]);
            $product->setRawAttributes($locked->getAttributes(), true);   // el objeto que nos pasaron queda al día

            $reference = $details['reference'] ?? null;

            return $locked->stockMovements()->create([
                'type' => $type,
                'reason' => $details['reason'] ?? null,
                'quantity' => $quantity,
                'stock_after' => $stockAfter,
                'unit_cost' => $details['unit_cost'] ?? $locked->cost_price,
                'note' => $details['note'] ?? null,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }

    /**
     * Ajuste manual: rotura, pérdida, devolución, corrección…
     */
    public function adjust(Product $product, int $quantity, AdjustmentReason $reason, ?string $note = null): StockMovement
    {
        return $this->record($product, StockMovementType::Adjustment, $quantity, compact('reason', 'note'));
    }

    /**
     * Conteo físico: se carga lo que hay y se registra la diferencia.
     * Devuelve null si el conteo coincide con el sistema (no hay nada que ajustar).
     */
    public function count(Product $product, int $counted, ?string $note = null): ?StockMovement
    {
        $difference = $counted - $product->fresh()->stock;

        if ($difference === 0) {
            return null;
        }

        return $this->record($product, StockMovementType::Count, $difference, compact('note'));
    }
}
