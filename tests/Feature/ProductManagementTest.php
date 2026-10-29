<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_index_lists_products_and_filters_by_stock(): void
    {
        Product::factory()->create(['name' => 'Sartén 24 cm', 'stock' => 50, 'min_stock' => 5, 'tax_rate' => 10.5]);
        Product::factory()->outOfStock()->create(['name' => 'Pava eléctrica']);

        $this->get('/erp/productos')
            ->assertOk()
            ->assertSee('Sartén 24 cm')
            ->assertSee('Pava eléctrica')
            ->assertSee('10,5%');

        $this->get('/erp/productos?stock=out')
            ->assertOk()
            ->assertSee('Pava eléctrica')
            ->assertDontSee('Sartén 24 cm');
    }

    public function test_search_matches_sku_and_barcode(): void
    {
        Product::factory()->create(['name' => 'Olla', 'sku' => 'BZ-11111', 'barcode' => '7790000000001']);
        Product::factory()->create(['name' => 'Colador', 'sku' => 'BZ-22222']);

        $this->get('/erp/productos?q=7790000000001')->assertSee('Olla')->assertDontSee('Colador');
        $this->get('/erp/productos?q=BZ-222')->assertSee('Colador')->assertDontSee('Olla');
    }

    public function test_create_form_renders(): void
    {
        $this->get('/erp/productos/create')->assertOk()->assertSee('Nuevo producto');
    }

    public function test_product_can_be_created_with_image(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $response = $this->post('/erp/productos', [
            'name' => 'Juego de cuchillos x6',
            'sku' => 'BZ-00001',
            'barcode' => '7791234567890',
            'category_id' => $category->id,
            'unit' => 'unidad',
            'cost_price' => '1000',
            'sale_price' => '1500',
            'tax_rate' => '21',
            'stock' => '10',
            'min_stock' => '2',
            'is_active' => '1',
            'is_published' => '0',
            'image' => UploadedFile::fake()->image('cuchillos.jpg'),
        ]);

        $product = Product::firstWhere('sku', 'BZ-00001');

        $response->assertRedirect(route('erp.products.show', $product));
        $this->assertSame('juego-de-cuchillos-x6', $product->slug);
        $this->assertEquals(1815.0, $product->final_price);
        $this->assertEquals(50.0, $product->margin);
        Storage::disk('public')->assertExists($product->image_path);

        $this->get(route('erp.products.show', $product))->assertOk()->assertSee('$ 1.815,00');
    }

    public function test_validation_errors_are_in_spanish(): void
    {
        Product::factory()->create(['sku' => 'BZ-DUP']);

        $this->post('/erp/productos', ['sku' => 'BZ-DUP'])
            ->assertSessionHasErrors([
                'name' => 'El campo nombre es obligatorio.',
                'sku' => 'Ya existe un producto con ese SKU.',
            ]);
    }

    public function test_product_can_be_updated_keeping_its_own_sku(): void
    {
        $product = Product::factory()->create(['sku' => 'BZ-12345', 'stock' => 3]);

        $this->get(route('erp.products.edit', $product))->assertOk();

        $this->put(route('erp.products.update', $product), [
            'name' => 'Nombre nuevo',
            'slug' => $product->slug,
            'sku' => 'BZ-12345',
            'unit' => 'caja',
            'cost_price' => '200',
            'sale_price' => '300',
            'tax_rate' => '10.5',
            'min_stock' => '5',
            'is_active' => '0',
            'is_published' => '1',
        ])->assertRedirect(route('erp.products.show', $product))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('Nombre nuevo', $product->name);
        $this->assertSame('low', $product->stock_status);
        $this->assertFalse($product->is_active);
        $this->assertTrue($product->is_published);
    }

    public function test_tax_rate_can_be_entered_manually(): void
    {
        $product = Product::factory()->create(['cost_price' => 100, 'sale_price' => 200]);
        $data = [...$product->only(['name', 'sku', 'unit', 'cost_price', 'sale_price', 'min_stock']), 'tax_rate' => '5'];

        $this->put(route('erp.products.update', $product), $data)->assertSessionHasNoErrors();
        $this->assertEquals(210.0, $product->refresh()->final_price);

        $this->put(route('erp.products.update', $product), [...$data, 'tax_rate' => '150'])
            ->assertSessionHasErrors(['tax_rate' => 'El IVA no puede ser mayor a 100%.']);
    }

    public function test_inline_margin_edit_recalculates_sale_price(): void
    {
        $product = Product::factory()->create(['cost_price' => 1000, 'sale_price' => 1200, 'tax_rate' => 21]);

        $this->patchJson(route('erp.products.pricing', $product), ['margin' => '50'])
            ->assertOk()
            ->assertJson([
                'margin' => 50,
                'margin_label' => '50%',
                'sale_price_label' => '$ 1.500,00',
                'final_price_label' => '$ 1.815,00',
            ]);

        $this->assertEquals(1500, $product->refresh()->sale_price);
    }

    public function test_inline_tax_edit_accepts_any_rate(): void
    {
        $product = Product::factory()->create(['cost_price' => 100, 'sale_price' => 200, 'tax_rate' => 21]);

        $this->patchJson(route('erp.products.pricing', $product), ['tax_rate' => '8.5'])
            ->assertOk()
            ->assertJson(['tax_label' => '8,5%', 'final_price_label' => '$ 217,00']);

        $this->patchJson(route('erp.products.pricing', $product), ['tax_rate' => '101'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.tax_rate.0', 'El IVA no puede ser mayor a 100%.');
    }

    public function test_inline_margin_edit_requires_cost(): void
    {
        $product = Product::factory()->create(['cost_price' => 0, 'sale_price' => 200]);

        $this->patchJson(route('erp.products.pricing', $product), ['margin' => '30'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.margin.0', 'Cargá el costo del producto para usar el margen.');

        $this->assertEquals(200, $product->refresh()->sale_price);
    }

    public function test_product_can_be_deleted(): void
    {
        $product = Product::factory()->create();

        $this->delete(route('erp.products.destroy', $product))->assertRedirect(route('erp.products.index'));

        $this->assertSoftDeleted($product);
    }

    public function test_category_can_be_changed_from_the_list(): void
    {
        $product = Product::factory()->create(['category_id' => null]);
        $category = Category::factory()->create();

        $this->patchJson(route('erp.products.quick-update', $product), ['category_id' => $category->id])
            ->assertOk()
            ->assertJson(['category_id' => $category->id]);
        $this->assertSame($category->id, $product->fresh()->category_id);

        $this->patchJson(route('erp.products.quick-update', $product), ['category_id' => null])
            ->assertJson(['category_id' => null]);
        $this->assertNull($product->fresh()->category_id);
    }

    public function test_web_channel_can_be_toggled_from_the_list(): void
    {
        $product = Product::factory()->create(['is_published' => false, 'name' => 'Taza', 'cost_price' => 100]);

        $this->patchJson(route('erp.products.quick-update', $product), ['is_published' => true])
            ->assertOk()
            ->assertJson(['is_published' => true]);

        // Solo cambia lo que se mandó.
        $product->refresh();
        $this->assertTrue($product->is_published);
        $this->assertSame('Taza', $product->name);
        $this->assertEquals(100, $product->cost_price);
    }

    public function test_quick_update_rejects_unknown_category(): void
    {
        $product = Product::factory()->create();

        $this->patchJson(route('erp.products.quick-update', $product), ['category_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }
}
