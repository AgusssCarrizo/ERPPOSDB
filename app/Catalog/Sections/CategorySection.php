<?php

namespace App\Catalog\Sections;

use App\Catalog\CatalogImages;
use App\Catalog\Section;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Una categoría: página de portada con su foto + páginas con sus productos.
 */
class CategorySection implements Section
{
    public const PRODUCTS_PER_PAGE = 3;

    /**
     * @param  Collection<int, Product>  $products
     */
    public function __construct(
        public readonly Category $category,
        public readonly Collection $products,
        private readonly CatalogImages $images,
    ) {}

    public function view(): string
    {
        return 'catalog.sections.category';
    }

    public function data(): array
    {
        return [
            'category' => $this->category,
            'cover' => $this->images->cover($this->category->image_path, 1200, 1100),
            'pages' => $this->products
                ->map(fn (Product $product) => [
                    'name' => $product->name,
                    'price' => price_tag($product->final_price),
                    'image' => $this->images->contain($product->image_path, 600, 600),
                ])
                ->chunk(self::PRODUCTS_PER_PAGE),
        ];
    }

    public function pageCount(): int
    {
        return 1 + (int) ceil($this->products->count() / self::PRODUCTS_PER_PAGE);
    }
}
