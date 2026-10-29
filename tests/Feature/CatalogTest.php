<?php

namespace Tests\Feature;

use App\Catalog\CatalogBuilder;
use App\Catalog\Sections\CategorySection;
use App\Catalog\Sections\CoverSection;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_builder_only_includes_active_products_with_stock(): void
    {
        $category = Category::factory()->create();
        $inStock = Product::factory()->create(['category_id' => $category->id, 'stock' => 5, 'is_active' => true]);
        Product::factory()->outOfStock()->create(['category_id' => $category->id]);
        Product::factory()->create(['category_id' => $category->id, 'stock' => 5, 'is_active' => false]);

        $sections = app(CatalogBuilder::class)->build('Catálogo', null, [$category->id]);

        $this->assertInstanceOf(CoverSection::class, $sections[0]);
        $this->assertInstanceOf(CategorySection::class, $sections[1]);
        $this->assertEquals([$inStock->id], $sections[1]->products->pluck('id')->all());
    }

    public function test_builder_orders_categories_and_skips_inactive_or_empty_ones(): void
    {
        $second = Category::factory()->create(['position' => 2]);
        $first = Category::factory()->create(['position' => 1]);
        $inactive = Category::factory()->create(['position' => 3, 'is_active' => false]);
        $empty = Category::factory()->create(['position' => 4]);

        foreach ([$first, $second, $inactive] as $category) {
            Product::factory()->create(['category_id' => $category->id, 'stock' => 3, 'is_active' => true]);
        }

        $sections = app(CatalogBuilder::class)->build('Catálogo', null, [$second->id, $first->id, $inactive->id, $empty->id]);

        $this->assertEquals(
            [$first->id, $second->id],
            collect($sections)->whereInstanceOf(CategorySection::class)->map(fn ($s) => $s->category->id)->values()->all(),
        );
    }

    public function test_page_count_is_cover_plus_category_pages(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(7)->create(['category_id' => $category->id, 'stock' => 3, 'is_active' => true]);

        $sections = app(CatalogBuilder::class)->build('Catálogo', null, [$category->id]);

        // portada + página de la categoría + 3 páginas de productos (3 + 3 + 1)
        $this->assertSame(5, CatalogBuilder::pageCount($sections));
        $this->assertSame(7, CatalogBuilder::productCount($sections));
    }

    public function test_index_shows_categories_and_upcoming_sections(): void
    {
        $category = Category::factory()->create(['name' => 'Vajilla']);
        Product::factory()->count(2)->create(['category_id' => $category->id, 'stock' => 3, 'is_active' => true]);

        $this->get('/erp/catalogo')
            ->assertOk()
            ->assertSee('Vajilla')
            ->assertSee('2 productos con stock')
            ->assertSee('Agregar sección')
            ->assertSee('Próximamente');
    }

    public function test_pdf_is_downloaded(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'stock' => 3, 'is_active' => true]);

        $response = $this->get(route('erp.catalog.pdf', ['title' => 'Catálogo', 'categories' => [$category->id]]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('attachment; filename=catalogo-', $response->headers->get('Content-Disposition'));
    }

    public function test_pdf_requires_categories_with_stock(): void
    {
        $category = Category::factory()->create();
        Product::factory()->outOfStock()->create(['category_id' => $category->id]);

        $this->from('/erp/catalogo')
            ->get(route('erp.catalog.pdf', ['title' => 'Catálogo']))
            ->assertRedirect('/erp/catalogo')
            ->assertSessionHasErrors(['categories' => 'Elegí al menos una categoría.']);

        $this->from('/erp/catalogo')
            ->get(route('erp.catalog.pdf', ['title' => 'Catálogo', 'categories' => [$category->id]]))
            ->assertSessionHasErrors(['categories' => 'Las categorías elegidas no tienen productos con stock.']);
    }
}
