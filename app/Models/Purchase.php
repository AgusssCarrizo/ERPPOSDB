<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mercadería recibida de un proveedor. Se registra con App\Services\Purchasing.
 */
#[Fillable(['supplier_id', 'date', 'invoice_number', 'subtotal', 'tax_amount', 'total', 'notes'])]
class Purchase extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * Número para mostrar: C-000012
     */
    protected function number(): Attribute
    {
        return Attribute::get(fn () => 'C-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }
}
