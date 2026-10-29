<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Producto que se puede vender en la tienda: publicado, activo y con stock.
     */
    private function storeProduct(array $attributes = []): Product
    {
        return Product::factory()->create([
            'is_published' => true,
            'is_active' => true,
            'stock' => 5,
            ...$attributes,
        ]);
    }

    public function test_home_lists_only_featured_products_available_in_store(): void
    {
        $featured = $this->storeProduct(['name' => 'Taza Nórdica', 'is_featured' => true]);
        $this->storeProduct(['name' => 'Mate sin corazón']);
        $this->storeProduct(['name' => 'Taza no publicada', 'is_featured' => true, 'is_published' => false]);
        $this->storeProduct(['name' => 'Taza agotada', 'is_featured' => true, 'stock' => 0]);
        $this->storeProduct(['name' => 'Taza inactiva', 'is_featured' => true, 'is_active' => false]);

        $hiddenCategory = Category::factory()->create(['is_active' => false]);
        $this->storeProduct(['name' => 'Taza de categoría inactiva', 'is_featured' => true, 'category_id' => $hiddenCategory->id]);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertViewHas('featured', fn ($products) => $products->pluck('id')->all() === [$featured->id])
            ->assertSee('Taza Nórdica')
            ->assertDontSee('Taza no publicada')
            ->assertDontSee('Taza agotada');
    }

    public function test_home_shows_categories_with_products_and_the_promo(): void
    {
        $withProducts = Category::factory()->create(['name' => 'Vajilla']);
        Category::factory()->create(['name' => 'Categoría vacía']);
        $promo = $this->storeProduct(['category_id' => $withProducts->id, 'sale_price' => 1000, 'tax_rate' => 21, 'compare_price' => 1500]);
        StoreSetting::current()->update(['promo_product_id' => $promo->id, 'promo_highlight' => 'Gourmet.']);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertViewHas('categories', fn ($categories) => $categories->pluck('id')->all() === [$withProducts->id])
            ->assertSee('Gourmet.')
            ->assertSee('Precio especial')
            ->assertSee('$1.210')
            ->assertSee('$1.500');
    }

    public function test_promo_is_hidden_when_its_product_has_no_stock(): void
    {
        $promo = $this->storeProduct(['stock' => 0]);
        StoreSetting::current()->update(['promo_product_id' => $promo->id]);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertViewHas('promoProduct', null);
    }

    public function test_category_page_lists_its_available_products(): void
    {
        $category = Category::factory()->create();
        $mine = $this->storeProduct(['category_id' => $category->id]);
        $this->storeProduct(['category_id' => $category->id, 'stock' => 0]);
        $this->storeProduct();

        $this->get(route('shop.categories.show', $category))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$mine->id]);
    }

    public function test_inactive_category_page_is_not_found(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $this->get(route('shop.categories.show', $category))->assertNotFound();
    }

    public function test_search_filters_by_name_and_sorts_by_final_price(): void
    {
        $cheap = $this->storeProduct(['name' => 'Taza chica', 'sale_price' => 1000, 'tax_rate' => 21]);
        $expensive = $this->storeProduct(['name' => 'Taza grande', 'sale_price' => 1100, 'tax_rate' => 0]);
        $this->storeProduct(['name' => 'Plato playo']);

        // 1000 + 21% = 1210 es más caro que 1100 sin IVA.
        $this->get(route('shop.products.index', ['q' => 'taza', 'orden' => 'price_asc']))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$expensive->id, $cheap->id]);
    }

    public function test_product_page_shows_price_with_tax_and_whatsapp_link(): void
    {
        $product = $this->storeProduct(['name' => 'Prensa francesa', 'sale_price' => 10000, 'tax_rate' => 21]);

        $this->get(route('shop.products.show', $product))
            ->assertOk()
            ->assertSee('Prensa francesa')
            ->assertSee('$12.100')
            ->assertSee('https://wa.me/5493388458046?text=', false)
            ->assertSee('Comprar por WhatsApp');
    }

    public function test_out_of_stock_product_page_is_still_visible(): void
    {
        $product = $this->storeProduct(['stock' => 0]);

        $this->get(route('shop.products.show', $product))
            ->assertOk()
            ->assertSee('Sin stock por ahora')
            ->assertSee('Consultar por WhatsApp');
    }

    public function test_unpublished_product_page_is_not_found(): void
    {
        $product = $this->storeProduct(['is_published' => false]);

        $this->get(route('shop.products.show', $product))->assertNotFound();
    }
}
