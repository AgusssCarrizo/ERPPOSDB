<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Carga masiva de productos nuevos desde una planilla (ver SpreadsheetReader).
 *
 * Primero se revisa todo (check) y se muestra cómo va a quedar; recién después se importa (import).
 * Solo crea productos: no modifica los que ya existen (el stock solo se mueve desde Inventario).
 */
class ProductImport
{
    /**
     * Nombres de columna aceptados -> campo. Los encabezados llegan normalizados ("Precio final" -> "precio_final").
     */
    private const COLUMNS = [
        'nombre' => 'name', 'producto' => 'name', 'articulo' => 'name',
        'categoria' => 'category', 'rubro' => 'category',
        'sku' => 'sku', 'codigo' => 'sku', 'cod' => 'sku', 'codigo_interno' => 'sku',
        'codigo_de_barras' => 'barcode', 'barcode' => 'barcode', 'ean' => 'barcode',
        'costo' => 'cost', 'precio_costo' => 'cost', 'precio_de_costo' => 'cost',
        'margen' => 'margin',
        'precio_venta' => 'net_price', 'precio_sin_iva' => 'net_price',
        // "precio" a secas se toma como lo que paga el cliente, con IVA.
        'precio_final' => 'final_price', 'precio' => 'final_price', 'precio_con_iva' => 'final_price', 'pvp' => 'final_price',
        'iva' => 'tax', 'alicuota' => 'tax',
        'stock' => 'stock', 'cantidad' => 'stock', 'existencia' => 'stock',
        'stock_minimo' => 'min_stock', 'minimo' => 'min_stock',
        'unidad' => 'unit',
        'descripcion' => 'description',
        'activo' => 'active',
        'tienda' => 'published', 'publicado' => 'published', 'web' => 'published',
    ];

    public const MAX_ROWS = 5000;

    /**
     * Interpreta y valida todas las filas. Nada se guarda.
     *
     * @param  list<array{line: int, values: array<string, string>}>  $rows
     * @return list<array{line: int, data: array<string, mixed>, errors: list<string>}>
     */
    public function check(array $rows): array
    {
        $existingSkus = Product::withTrashed()->pluck('sku')->map(fn ($sku) => mb_strtolower($sku))->flip();
        $existingBarcodes = Product::withTrashed()->whereNotNull('barcode')->pluck('barcode')->flip();
        $existingNames = Product::pluck('name')->map(fn ($name) => mb_strtolower($name))->flip();
        $categories = Category::pluck('name')->map(fn ($name) => mb_strtolower($name))->flip();

        $seen = ['sku' => [], 'barcode' => [], 'name' => []];
        $result = [];

        foreach ($rows as $row) {
            [$data, $errors] = $this->interpret($this->fields($row['values']));

            $name = mb_strtolower($data['name']);
            if ($name !== '' && ($existingNames->has($name) || isset($seen['name'][$name]))) {
                $errors[] = isset($seen['name'][$name]) ? 'Nombre repetido en la planilla.' : 'Ya existe un producto con este nombre.';
            }

            $sku = mb_strtolower((string) $data['sku']);
            if ($sku !== '' && ($existingSkus->has($sku) || isset($seen['sku'][$sku]))) {
                $errors[] = "El SKU {$data['sku']} ya está usado.";
            }

            if ($data['barcode'] !== null && ($existingBarcodes->has($data['barcode']) || isset($seen['barcode'][$data['barcode']]))) {
                $errors[] = "El código de barras {$data['barcode']} ya está usado.";
            }

            $seen['name'][$name] = true;
            $seen['sku'][$sku] = true;
            $seen['barcode'][(string) $data['barcode']] = true;

            $data['new_category'] = $data['category'] !== null && ! $categories->has(mb_strtolower($data['category']));

            $result[] = ['line' => $row['line'], 'data' => $data, 'errors' => $errors];
        }

        return $result;
    }

    /**
     * Columnas de la planilla que no se reconocen (para avisar que se van a ignorar).
     *
     * @param  list<array{line: int, values: array<string, string>}>  $rows
     * @return list<string>
     */
    public function unknownColumns(array $rows): array
    {
        return array_values(array_diff(array_keys($rows[0]['values'] ?? []), array_keys(self::COLUMNS)));
    }

    /**
     * Crea los productos (y las categorías que falten) en una sola transacción.
     * El stock de cada uno queda como movimiento "Stock inicial" (evento created de Product).
     *
     * @param  list<array{line: int, data: array<string, mixed>, errors: list<string>}>  $checked  resultado de check(), sin errores
     * @return array{products: int, categories: list<string>}
     */
    public function import(array $checked): array
    {
        return DB::transaction(function () use ($checked) {
            $categories = Category::all()->keyBy(fn (Category $category) => mb_strtolower($category->name));
            $newCategories = [];
            $usedSkus = Product::withTrashed()->pluck('sku')->flip();
            $nextSku = 1;

            foreach ($checked as $row) {
                $data = $row['data'];
                $category = null;

                if ($data['category'] !== null) {
                    $key = mb_strtolower($data['category']);

                    if (! $categories->has($key)) {
                        // Se crea sin foto: hay que cargarle la portada antes de usarla en el catálogo.
                        $categories[$key] = Category::create(['name' => $data['category'], 'position' => $categories->count(), 'is_active' => true]);
                        $newCategories[] = $data['category'];
                    }

                    $category = $categories[$key];
                }

                // Sin SKU en la planilla: se genera DB-00001, DB-00002…
                $sku = $data['sku'];
                while ($sku === null) {
                    $candidate = 'DB-'.str_pad((string) $nextSku++, 5, '0', STR_PAD_LEFT);
                    $sku = $usedSkus->has($candidate) ? null : $candidate;
                }
                $usedSkus[$sku] = true;

                Product::create([
                    'category_id' => $category?->id,
                    'name' => $data['name'],
                    'sku' => $sku,
                    'barcode' => $data['barcode'],
                    'description' => $data['description'],
                    'unit' => $data['unit'],
                    'cost_price' => $data['cost'],
                    'sale_price' => $data['net_price'],
                    'tax_rate' => $data['tax'],
                    'stock' => $data['stock'],
                    'min_stock' => $data['min_stock'],
                    'is_active' => $data['active'],
                    'is_published' => $data['published'],
                ]);
            }

            return ['products' => count($checked), 'categories' => $newCategories];
        });
    }

    /**
     * Pasa de columnas de la planilla a campos (la primera columna que aparezca gana).
     *
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    private function fields(array $values): array
    {
        $fields = [];

        foreach ($values as $column => $value) {
            $field = self::COLUMNS[$column] ?? null;

            if ($field && ($fields[$field] ?? '') === '') {
                $fields[$field] = $value;
            }
        }

        return $fields;
    }

    /**
     * Convierte los textos de una fila en datos de producto, con sus errores.
     *
     * @param  array<string, string>  $fields
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function interpret(array $fields): array
    {
        $errors = [];
        $get = fn (string $field) => ($fields[$field] ?? '') === '' ? null : $fields[$field];

        $name = Str::limit((string) $get('name'), 255, '');
        if ($name === '') {
            $errors[] = 'Falta el nombre.';
        }

        $cost = $this->number($get('cost'));
        if ($get('cost') === null) {
            $errors[] = 'Falta el costo.';
        } elseif ($cost === null || $cost < 0) {
            $errors[] = "El costo \"{$get('cost')}\" no es un número válido.";
        }

        $tax = $get('tax') === null ? 21.0 : $this->percentage($get('tax'));
        if ($tax === null || $tax > 100) {
            $errors[] = "El IVA \"{$get('tax')}\" no es válido (va de 0 a 100).";
            $tax = 21.0;
        }

        // Precio de venta (sin IVA): se calcula desde lo que haya en la planilla, en este orden.
        $netPrice = null;
        if ($get('final_price') !== null) {
            $final = $this->number($get('final_price'));

            if ($final === null || $final < 0) {
                $errors[] = "El precio \"{$get('final_price')}\" no es un número válido.";
            } else {
                $netPrice = round($final / (1 + $tax / 100), 2);
            }
        } elseif ($get('net_price') !== null) {
            $netPrice = $this->number($get('net_price'));
            if ($netPrice === null || $netPrice < 0) {
                $errors[] = "El precio de venta \"{$get('net_price')}\" no es un número válido.";
            }
        } elseif ($get('margin') !== null) {
            $margin = $this->percentage($get('margin'));

            if ($margin === null) {
                $errors[] = "El margen \"{$get('margin')}\" no es válido.";
            } elseif ($cost !== null) {
                $netPrice = round($cost * (1 + $margin / 100), 2);   // margen sobre el costo
            }
        } else {
            $errors[] = 'Falta el precio (completá precio, precio_venta o margen).';
        }

        $stock = $this->integer($get('stock') ?? '0');
        if ($stock === null || $stock < 0) {
            $errors[] = "El stock \"{$get('stock')}\" tiene que ser un número entero, 0 o más.";
        }

        $minStock = $this->integer($get('min_stock') ?? '0');
        if ($minStock === null || $minStock < 0) {
            $errors[] = "El stock mínimo \"{$get('min_stock')}\" tiene que ser un número entero, 0 o más.";
        }

        $unit = $this->unit($get('unit'));
        if ($unit === null) {
            $errors[] = "La unidad \"{$get('unit')}\" no existe (usá: ".implode(', ', array_keys(Product::UNITS)).').';
        }

        $sku = $get('sku');
        if ($sku !== null && mb_strlen($sku) > 64) {
            $errors[] = 'El SKU es demasiado largo (máximo 64).';
        }

        $data = [
            'name' => $name,
            'category' => $get('category') === null ? null : Str::limit($get('category'), 255, ''),
            'sku' => $sku,
            // Excel muestra los códigos largos como 7.79E+12: se pide guardarlos como texto.
            'barcode' => $get('barcode'),
            'description' => $get('description'),
            'unit' => $unit ?? 'unidad',
            'cost' => $cost ?? 0.0,
            'net_price' => $netPrice ?? 0.0,
            'tax' => $tax,
            'final_price' => round(($netPrice ?? 0) * (1 + $tax / 100), 2),
            'margin' => $cost > 0 && $netPrice !== null ? round(($netPrice - $cost) / $cost * 100, 1) : null,
            'stock' => $stock ?? 0,
            'min_stock' => $minStock ?? 0,
            'active' => $this->boolean($get('active'), true),
            'published' => $this->boolean($get('published'), false),
        ];

        return [$data, $errors];
    }

    /**
     * Acepta los formatos que suelen salir de Excel: 1500 · 1500,50 · 1.500,50 · $ 1.500 · 45,000 · 1500.5
     */
    private function number(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace(['$', ' ', "\u{00A0}"], '', $value);
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            // El separador que aparece último es el decimal.
            $value = $lastComma > $lastDot
                ? str_replace(['.', ','], ['', '.'], $value)
                : str_replace(',', '', $value);
        } elseif ($lastComma !== false) {
            // "45,000" son miles (formato de las historias); "1500,5" es decimal.
            $value = preg_match('/^-?\d{1,3}(,\d{3})+$/', $value) ? str_replace(',', '', $value) : str_replace(',', '.', $value);
        } elseif ($lastDot !== false && preg_match('/^-?\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);   // "1.500" son miles
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Porcentajes: "21", "21%", "10,5". Si la celda de Excel tiene formato %, llega 0.21: se pasa a 21.
     */
    private function percentage(string $value): ?float
    {
        $hasSymbol = str_contains($value, '%');
        $number = $this->number(str_replace('%', '', $value));

        if ($number === null || $number < 0) {
            return null;
        }

        return ! $hasSymbol && $number > 0 && $number < 1 ? round($number * 100, 2) : $number;
    }

    private function integer(string $value): ?int
    {
        $number = $this->number($value);

        return $number !== null && floor($number) === $number ? (int) $number : null;
    }

    private function unit(?string $value): ?string
    {
        if ($value === null) {
            return 'unidad';
        }

        $value = Str::slug($value);

        return collect(Product::UNITS)->search(fn ($label, $key) => $key === $value || Str::slug($label) === $value) ?: null;
    }

    private function boolean(?string $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        return in_array(Str::slug($value), ['si', 's', '1', 'x', 'true', 'verdadero', 'yes'], true);
    }
}
