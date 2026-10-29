<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_index_lists_categories_and_warns_about_missing_photos(): void
    {
        Category::factory()->create(['name' => 'Vajilla', 'image_path' => null]);

        $this->get('/erp/categorias')
            ->assertOk()
            ->assertSee('Vajilla')
            ->assertSee('1 categoría no tiene');
    }

    public function test_category_requires_a_photo_when_created(): void
    {
        $this->post('/erp/categorias', ['name' => 'Cocina', 'position' => 1])
            ->assertSessionHasErrors(['image' => 'Cargá una foto: se usa como portada de la categoría en el catálogo.']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_category_can_be_created_with_photo(): void
    {
        $this->get('/erp/categorias/create')->assertOk();

        $this->post('/erp/categorias', [
            'name' => 'Cocina y Mesa',
            'position' => 3,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('cocina.jpg', 800, 600),
        ])->assertRedirect(route('erp.categories.index'));

        $category = Category::firstWhere('name', 'Cocina y Mesa');
        $this->assertSame('cocina-y-mesa', $category->slug);
        $this->assertSame(3, $category->position);
        Storage::disk('public')->assertExists($category->image_path);
    }

    public function test_photo_is_optional_when_editing_and_replaced_when_uploaded(): void
    {
        $old = UploadedFile::fake()->image('vieja.jpg')->store('categories', 'public');
        $category = Category::factory()->create(['image_path' => $old]);

        $this->get(route('erp.categories.edit', $category))->assertOk();

        $this->put(route('erp.categories.update', $category), ['name' => 'Nuevo nombre', 'position' => 2])
            ->assertSessionHasNoErrors();
        $this->assertSame($old, $category->refresh()->image_path);

        $this->put(route('erp.categories.update', $category), [
            'name' => 'Nuevo nombre',
            'position' => 2,
            'image' => UploadedFile::fake()->image('nueva.jpg'),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($category->refresh()->image_path);
    }

    public function test_deleting_a_category_keeps_its_products(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->delete(route('erp.categories.destroy', $category))->assertRedirect(route('erp.categories.index'));

        $this->assertModelMissing($category);
        $this->assertNull($product->refresh()->category_id);
    }
}
