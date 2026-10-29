<?php

namespace App\Enums;

/**
 * Medios de cobro del mostrador. (Los pagos a proveedores usan App\Enums\PaymentMethod.)
 */
enum SalePaymentMethod: string
{
    case Cash = 'cash';
    case Debit = 'debit';
    case Credit = 'credit';
    case MercadoPago = 'mercado_pago';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Debit => 'Tarjeta de débito',
            self::Credit => 'Tarjeta de crédito',
            self::MercadoPago => 'Mercado Pago',
        };
    }

    /**
     * Nombre corto para los botones del POS.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Debit => 'Débito',
            self::Credit => 'Crédito',
            self::MercadoPago => 'Mercado Pago',
        };
    }

    /**
     * Ícono de <x-icon> para los botones (Mercado Pago = QR).
     */
    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'banknotes',
            self::Debit, self::Credit => 'credit-card',
            self::MercadoPago => 'barcode',
        };
    }

    /**
     * Solo en efectivo hay vuelto.
     */
    public function givesChange(): bool
    {
        return $this === self::Cash;
    }

    /**
     * Clases de Tailwind para la etiqueta en los listados.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Cash => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Debit => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            self::Credit => 'bg-violet-50 text-violet-700 ring-violet-600/20',
            self::MercadoPago => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        };
    }
}
