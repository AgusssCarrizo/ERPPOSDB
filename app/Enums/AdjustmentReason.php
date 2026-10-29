<?php

namespace App\Enums;

/**
 * Motivos de un ajuste manual de stock. Cada uno indica si suma, resta o puede ser ambos.
 */
enum AdjustmentReason: string
{
    case Breakage = 'breakage';
    case Loss = 'loss';
    case Theft = 'theft';
    case InternalUse = 'internal_use';
    case Gift = 'gift';
    case Found = 'found';
    case CustomerReturn = 'customer_return';
    case Correction = 'correction';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Breakage => 'Rotura',
            self::Loss => 'Pérdida',
            self::Theft => 'Robo',
            self::InternalUse => 'Uso interno',
            self::Gift => 'Regalo / donación',
            self::Found => 'Mercadería encontrada',
            self::CustomerReturn => 'Devolución de cliente',
            self::Correction => 'Corrección de carga',
            self::Other => 'Otro',
        };
    }

    /**
     * 'in' solo suma, 'out' solo resta, 'both' cualquiera de los dos.
     */
    public function direction(): string
    {
        return match ($this) {
            self::Breakage, self::Loss, self::Theft, self::InternalUse, self::Gift => 'out',
            self::Found, self::CustomerReturn => 'in',
            self::Correction, self::Other => 'both',
        };
    }

    public function allows(string $direction): bool
    {
        return in_array($this->direction(), [$direction, 'both'], true);
    }
}
