<?php

namespace App\Models;

use App\Enums\AdjustmentReason;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un movimiento de stock. No se edita ni se borra: si algo estuvo mal, se corrige con otro movimiento.
 */
#[Fillable([
    'product_id', 'type', 'reason', 'quantity', 'stock_after', 'unit_cost', 'note',
    'reference_type', 'reference_id', 'user_id',
])]
class StockMovement extends Model
{
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'reason' => AdjustmentReason::class,
            'quantity' => 'integer',
            'stock_after' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * Lo que generó el movimiento (una compra, una venta…).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Filtros del listado de inventario.
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['product'] ?? null, fn (Builder $query, $id) => $query->where('product_id', $id))
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                $query->whereHas('product', fn (Builder $query) => $query->withTrashed()
                    ->where(fn (Builder $query) => $query->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")));
            })
            ->when($filters['type'] ?? null, fn (Builder $query, $type) => $query->where('type', $type))
            ->when($filters['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
    }
}
