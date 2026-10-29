<?php

namespace App\Catalog;

use App\Catalog\Sections\CategorySection;
use App\Catalog\Sections\CoverSection;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Arma la lista de secciones del catálogo a partir de lo elegido en pantalla.
 */
class CatalogBuilder
{
    public function __construct(private readonly CatalogImages $images) {}

    /**
     * Productos que entran al catálogo: activos y con stock.
     */
    public static function productsQuery(Builder|HasMany $query): Builder|HasMany
    {
        return $query->where('is_active', true)->where('stock', '>', 0);
    }

    /**
     * Categorías activas con la cantidad de productos que entrarían al catálogo.
     *
     * @return Collection<int, Category>
     */
    public function availableCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->withCount(['products as catalog_products_count' => fn (Builder $query) => self::productsQuery($query)])
            ->ordered()
            ->get();
    }

    /**
     * @param  list<int>  $categoryIds
     * @return list<Section>
     */
    public function build(string $title, ?string $subtitle, array $categoryIds): array
    {
        $sections = [new CoverSection($title, $subtitle)];

        $categories = Category::query()
            ->whereKey($categoryIds)
            ->where('is_active', true)
            ->with(['products' => fn (HasMany $query) => self::productsQuery($query)->orderBy('name')])
            ->ordered()
            ->get();

        foreach ($categories as $category) {
            if ($category->products->isEmpty()) {
                continue;
            }

            $sections[] = new CategorySection($category, $category->products, $this->images);
        }

        // Acá se van a sumar las próximas secciones (ej. SectionType::Discounts).

        return $sections;
    }

    /**
     * @param  list<Section>  $sections
     */
    public static function pageCount(array $sections): int
    {
        return array_sum(array_map(fn (Section $section) => $section->pageCount(), $sections));
    }

    /**
     * @param  list<Section>  $sections
     */
    public static function productCount(array $sections): int
    {
        return array_sum(array_map(
            fn (Section $section) => $section instanceof CategorySection ? $section->products->count() : 0,
            $sections,
        ));
    }
}
