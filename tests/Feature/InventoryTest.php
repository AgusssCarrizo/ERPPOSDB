<?php

namespace Tests\Feature;

use App\Enums\AdjustmentReason;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Services\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_creating_a_product_records_its_initial_stock(): void
    {
        $product = Product::factory()->create(['stock' => 12]);

        $movement = $product->stockMovements()->sole();
        $this->assertSame(StockMovementType::Initial, $movement->type);
        $this->assertSame(12, $movement->quantity);
        $this->assertSame(12, $movement->stock_after);
    }

    public function test_products_created_without_stock_have_no_movements(): void
    {
        $product = Product::factory()->outOfStock()->create();

        $this->assertSame(0, $product->stockMovements()->count());
    }

    public function test_adjustment_changes_stock_and_records_the_movement(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->post(route('erp.inventory.store'), [
            'product_id' => $product->id,
            'mode' => 'remove',
            'quantity' => 2,
            'reason' => 'breakage',
            'note' => 'Se cayó de la góndola',
        ])->assertRedirect(route('erp.products.show', $product))->assertSessionHas('success');

        $this->assertSame(8, $product->refresh()->stock);

        $movement = $product->stockMovements()->latest('id')->first();
        $this->assertSame(-2, $movement->quantity);
        $this->assertSame(8, $movement->stock_after);
        $this->assertSame(AdjustmentReason::Breakage, $movement->reason);
        $this->assertSame('Se cayó de la góndola', $movement->note);
    }

    public function test_stock_cannot_go_below_zero(): void
    {
        $product = Product::factory()->create(['stock' => 3]);

        $this->post(route('erp.inventory.store'), [
            'product_id' => $product->id, 'mode' => 'remove', 'quantity' => 5, 'reason' => 'loss',
        ])->assertSessionHasErrors(['quantity' => 'No alcanza el stock: hay 3 y querés sacar 5.']);

        $this->assertSame(3, $product->refresh()->stock);
    }

    public function test_reason_must_match_the_direction(): void
    {
        $product = Product::factory()->create(['stock' => 3]);

        $this->post(route('erp.inventory.store'), [
            'product_id' => $product->id, 'mode' => 'add', 'quantity' => 1, 'reason' => 'breakage',
        ])->assertSessionHasErrors(['reason' => '"Rotura" no corresponde a sumar stock.']);
    }

    public function test_count_records_only_the_difference(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->post(route('erp.inventory.store'), [
            'product_id' => $product->id, 'mode' => 'count', 'quantity' => 7, 'return' => 'inventory',
        ])->assertRedirect(route('erp.inventory.index'));

        $movement = $product->stockMovements()->latest('id')->first();
        $this->assertSame(StockMovementType::Count, $movement->type);
        $this->assertSame(-3, $movement->quantity);
        $this->assertSame(7, $product->refresh()->stock);
    }

    public function test_count_matching_the_system_records_nothing(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->post(route('erp.inventory.store'), ['product_id' => $product->id, 'mode' => 'count', 'quantity' => 10])
            ->assertSessionHas('success', "El conteo de \"{$product->name}\" coincide con el sistema (10). No hizo falta ajustar.");

        $this->assertSame(1, $product->stockMovements()->count());   // solo el inicial
    }

    public function test_service_rejects_negative_stock(): void
    {
        $product = Product::factory()->create(['stock' => 1]);

        $this->expectException(ValidationException::class);

        app(Inventory::class)->adjust($product, -2, AdjustmentReason::Loss);
    }

    public function test_editing_a_product_cannot_change_its_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->put(route('erp.products.update', $product), [
            ...$product->only(['name', 'sku', 'unit', 'cost_price', 'sale_price', 'tax_rate', 'min_stock']),
            'stock' => 50,
        ])->assertSessionHasErrors(['stock' => 'El stock se modifica desde Inventario → Ajustar stock.']);

        $this->assertSame(5, $product->refresh()->stock);
    }

    public function test_inventory_pages_render(): void
    {
        $product = Product::factory()->create(['name' => 'Olla 5 L', 'stock' => 4]);
        app(Inventory::class)->adjust($product, 2, AdjustmentReason::Found);

        $this->get(route('erp.inventory.index'))->assertOk()->assertSee('Olla 5 L')->assertSee('Mercadería encontrada');
        $this->get(route('erp.inventory.index', ['product' => $product->id, 'type' => 'adjustment']))->assertOk()->assertSee('+2');
        $this->get(route('erp.inventory.create', ['product' => $product->id]))->assertOk();
        $this->get(route('erp.products.show', $product))->assertOk()->assertSee('Movimientos de stock')->assertSee('Stock inicial');
    }
}
