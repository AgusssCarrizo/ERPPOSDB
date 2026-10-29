<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function validData(array $overrides = []): array
    {
        return [
            ...StoreSetting::DEFAULTS,
            'hero_title' => 'Hogar con',
            'hero_highlight' => 'estilo.',
            ...$overrides,
        ];
    }

    public function test_heart_toggles_featured_product(): void
    {
        $product = Product::factory()->create(['is_featured' => false]);

        $this->patchJson(route('erp.products.featured', $product))
            ->assertOk()
            ->assertJson(['is_featured' => true]);
        $this->assertTrue($product->fresh()->is_featured);

        $this->patchJson(route('erp.products.featured', $product))
            ->assertJson(['is_featured' => false]);
        $this->assertFalse($product->fresh()->is_featured);
    }

    public function test_settings_page_starts_with_design_defaults(): void
    {
        $this->get(route('erp.store.edit'))
            ->assertOk()
            ->assertSee('Momentos que');

        $this->assertSame(1, StoreSetting::count());
    }

    public function test_settings_are_saved_with_hero_image(): void
    {
        Storage::fake('public');
        $promo = Product::factory()->create(['is_published' => true, 'is_active' => true]);

        $this->put(route('erp.store.update'), $this->validData([
            'promo_product_id' => $promo->id,
            'hero_image' => UploadedFile::fake()->image('portada.jpg', 1600, 800),
        ]))->assertRedirect(route('erp.store.edit'));

        $settings = StoreSetting::current();
        $this->assertSame('Hogar con', $settings->hero_title);
        $this->assertSame($promo->id, $settings->promo_product_id);
        Storage::disk('public')->assertExists($settings->hero_image_path);

        $this->get(route('shop.home'))->assertSee('Hogar con')->assertSee('estilo.');
    }

    public function test_settings_require_the_three_benefits(): void
    {
        $this->put(route('erp.store.update'), $this->validData(['benefits' => [['title' => 'Solo uno', 'text' => 'Falta el resto']]]))
            ->assertSessionHasErrors('benefits');
    }

    public function test_whatsapp_link_keeps_only_digits(): void
    {
        $settings = new StoreSetting(['whatsapp' => '+54 9 (3388) 458-046']);

        $this->assertSame('https://wa.me/5493388458046?text=Hola%20%21', $settings->whatsappUrl('Hola !'));
    }
}
