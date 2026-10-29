<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venta del mostrador. Se registra con App\Services\Selling y no se edita.
 */
#[Fillable(['subtotal', 'discount', 'total', 'notes'])]
class Sale extends Model
{
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    /**
     * Filtros del listado de ventas: rango de fechas y medio de pago.
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['method'] ?? null, fn (Builder $query, $method) => $query->whereHas('payments', fn (Builder $query) => $query->where('method', $method)));
    }

    /**
     * Número para mostrar: V-000012
     */
    protected function number(): Attribute
    {
        return Attribute::get(fn () => 'V-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }

    /**
     * Vuelto entregado (solo pagos en efectivo con "paga con").
     */
    protected function change(): Attribute
    {
        return Attribute::get(fn () => round($this->payments->sum(fn (SalePayment $payment) => $payment->change), 2));
    }

    /**
     * Ganancia bruta: lo cobrado sin IVA menos el costo de lo vendido.
     * El descuento se reparte en proporción entre los productos.
     */
    protected function profit(): Attribute
    {
        return Attribute::get(function () {
            if ((float) $this->subtotal <= 0) {
                return 0.0;
            }

            $keptRatio = (float) $this->total / (float) $this->subtotal;

            return round($this->items->sum(function (SaleItem $item) use ($keptRatio) {
                $netPrice = (float) $item->unit_price / (1 + (float) $item->tax_rate / 100);

                return ($netPrice * $keptRatio - (float) $item->unit_cost) * $item->quantity;
            }), 2);
        });
    }
}
