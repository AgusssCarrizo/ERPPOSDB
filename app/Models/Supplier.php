<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'cuit', 'contact_name', 'phone', 'email', 'address', 'notes'])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * Suma compras y pagos en la misma consulta, para mostrar la deuda en listados.
     */
    #[Scope]
    protected function withBalance(Builder $query): void
    {
        $query->withSum('purchases as purchases_total', 'total')
            ->withSum('payments as payments_total', 'amount');
    }

    /**
     * Lo que se le debe al proveedor. Negativo = saldo a favor nuestro.
     */
    protected function balance(): Attribute
    {
        return Attribute::get(function () {
            $purchases = $this->purchases_total ?? $this->purchases()->sum('total');
            $payments = $this->payments_total ?? $this->payments()->sum('amount');

            return round((float) $purchases - (float) $payments, 2);
        });
    }
}
