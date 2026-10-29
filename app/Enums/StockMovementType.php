<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Initial = 'initial';
    case Adjustment = 'adjustment';
    case Count = 'count';
    case Purchase = 'purchase';
    case Sale = 'sale';         // lo va a generar el POS / la tienda online

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Stock inicial',
            self::Adjustment => 'Ajuste',
            self::Count => 'Conteo',
            self::Purchase => 'Compra',
            self::Sale => 'Venta',
        };
    }

    /**
     * Clases de Tailwind para la etiqueta en los listados.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Initial => 'bg-zinc-100 text-zinc-700 ring-zinc-500/20',
            self::Adjustment => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            self::Count => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            self::Purchase => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            self::Sale => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        };
    }
}
