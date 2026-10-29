<?php

namespace App\Catalog;

/**
 * Una sección del catálogo PDF (portada, una categoría, descuentos…).
 *
 * Cada sección dibuja una o más páginas A4 con su propia vista Blade, en
 * resources/views/catalog/sections. Para sumar un tipo de sección nuevo:
 *   1. Crear la clase en app/Catalog/Sections implementando esta interfaz.
 *   2. Crear su vista.
 *   3. Activarla en SectionType y armarla en CatalogBuilder.
 */
interface Section
{
    /**
     * Nombre de la vista Blade que dibuja las páginas de la sección.
     */
    public function view(): string;

    /**
     * Datos que recibe la vista.
     *
     * @return array<string, mixed>
     */
    public function data(): array;

    /**
     * Cantidad de páginas que ocupa (para el resumen antes de generar).
     */
    public function pageCount(): int;
}
