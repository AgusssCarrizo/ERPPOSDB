<?php

namespace App\Catalog;

/**
 * Tipos de sección que puede tener el catálogo. Los que no están disponibles
 * aparecen en "Agregar sección" como "Próximamente".
 */
enum SectionType: string
{
    case Cover = 'cover';
    case Categories = 'categories';
    case Discounts = 'discounts';

    public function label(): string
    {
        return match ($this) {
            self::Cover => 'Portada',
            self::Categories => 'Categorías',
            self::Discounts => 'Descuentos',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cover => 'Título del catálogo y datos de contacto.',
            self::Categories => 'Una página con la foto de cada categoría y sus productos, de a 3 por página.',
            self::Discounts => 'Productos en oferta con el precio anterior tachado.',
        };
    }

    public function isAvailable(): bool
    {
        return $this !== self::Discounts;
    }

    /**
     * Secciones que se pueden sumar desde "Agregar sección".
     *
     * @return list<self>
     */
    public static function addable(): array
    {
        return [self::Discounts];
    }
}
