<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ProductStoryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductStoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_story_is_a_1080x1920_png(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create([
            'image_path' => UploadedFile::fake()->image('foto.jpg', 400, 600)->store('products', 'public'),
        ]);

        $response = $this->get(route('erp.products.story', $product))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        [$width, $height] = getimagesizefromstring($response->getContent());
        $this->assertSame([1080, 1920], [$width, $height]);
    }

    public function test_story_can_be_downloaded(): void
    {
        $product = Product::factory()->create(['slug' => 'vela-aromatica']);

        $this->get(route('erp.products.story', ['product' => $product, 'descargar' => 1]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="historia-vela-aromatica.png"');
    }

    public function test_story_renders_without_photo_and_with_very_long_names(): void
    {
        $product = Product::factory()->create([
            'name' => str_repeat('Juego de ollas de acero inoxidable ', 6),
            'image_path' => null,
        ]);

        $this->get(route('erp.products.story', $product))->assertOk();
    }

    public function test_price_uses_the_design_format(): void
    {
        $story = new ProductStoryImage;

        $this->assertSame('45,000', $story->formatPrice(45000));
        $this->assertSame('1,250,000', $story->formatPrice('1249999.60'));
    }
}
