<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purchase_id', 'product_id', 'quantity', 'unit_cost', 'previous_cost', 'subtotal', 'price_reviewed_at'])]
class PurchaseItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'previous_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'price_reviewed_at' => 'datetime',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * Ítems cuyo costo cambió y todavía nadie revisó el precio de venta.
     */
    #[Scope]
    protected function pendingPriceReview(Builder $query): void
    {
        $query->whereNull('price_reviewed_at');
    }

    protected function costChanged(): Attribute
    {
        return Attribute::get(fn () => (float) $this->unit_cost !== (float) $this->previous_cost);
    }

    /**
     * Precio de venta (sin IVA) que mantiene el margen que el producto tenía con el costo anterior.
     */
    protected function suggestedPrice(): Attribute
    {
        return Attribute::get(function () {
            if ((float) $this->previous_cost <= 0) {
                return (float) $this->product->sale_price;
            }

            $ratio = (float) $this->product->sale_price / (float) $this->previous_cost;

            return round((float) $this->unit_cost * $ratio, 2);
        });
    }
}
