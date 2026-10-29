<?php

namespace App\Models;

use App\Enums\SalePaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sale_id', 'method', 'amount', 'tendered'])]
class SalePayment extends Model
{
    protected function casts(): array
    {
        return [
            'method' => SalePaymentMethod::class,
            'amount' => 'decimal:2',
            'tendered' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Vuelto: con cuánto pagó menos lo que se cobró.
     */
    protected function change(): Attribute
    {
        return Attribute::get(fn () => $this->tendered === null ? 0.0 : round((float) $this->tendered - (float) $this->amount, 2));
    }
}
