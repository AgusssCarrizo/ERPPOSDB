<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Catálogo de ejemplo de un bazar: categoría => productos.
     */
    private const CATALOG = [
        'Cocina' => [
            'Sartén antiadherente 24 cm', 'Olla de acero inoxidable 5 L', 'Juego de cuchillos x6',
            'Tabla de picar de bambú', 'Pava eléctrica 1,7 L', 'Colador de acero 20 cm',
            'Espátula de silicona', 'Rallador 4 caras', 'Abrelatas manual',
        ],
        'Vajilla' => [
            'Juego de platos x18', 'Vaso de vidrio 350 ml x6', 'Taza de cerámica 300 ml',
            'Copa de vino x4', 'Jarra de vidrio 1,5 L', 'Set de cubiertos x24',
        ],
        'Limpieza' => [
            'Balde plástico 10 L', 'Escoba de cerdas suaves', 'Secador de piso 40 cm',
            'Trapo de piso x3', 'Esponja multiuso x5', 'Cesto de residuos 20 L',
        ],
        'Organización' => [
            'Caja organizadora 30 L', 'Perchero de pie', 'Set de frascos herméticos x4',
            'Canasto de mimbre', 'Organizador de cajón',
        ],
        'Iluminación' => [
            'Lámpara LED 12 W', 'Velador de mesa', 'Guirnalda de luces 5 m', 'Linterna recargable',
        ],
        'Regalería' => [
            'Portarretrato 15x20', 'Vela aromática', 'Florero de cerámica', 'Reloj de pared 30 cm',
        ],
    ];

    private const BRANDS = ['Tramontina', 'Carol', 'Rigolleau', 'Hudson', 'Colombraro', 'Marinex', 'Genérico'];

    public function run(): void
    {
        $brands = collect(self::BRANDS)->map(fn (string $name) => Brand::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]));

        $position = 0;

        foreach (self::CATALOG as $categoryName => $productNames) {
            $category = Category::create([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName),
                'position' => ++$position,
            ]);

            foreach ($productNames as $productName) {
                $factory = Product::factory();

                // Algunos productos con stock bajo o agotado para ver las alertas.
                $factory = match (fake()->numberBetween(1, 8)) {
                    1 => $factory->outOfStock(),
                    2 => $factory->lowStock(),
                    default => $factory,
                };

                $factory->create([
                    'name' => $productName,
                    'category_id' => $category->id,
                    'brand_id' => $brands->random()->id,
                ]);
            }
        }
    }
}
