<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PurchasingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function purchase(Supplier $supplier, array $items, array $extra = []): TestResponse
    {
        return $this->post(route('erp.purchases.store'), [
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'items' => $items,
            ...$extra,
        ]);
    }

    public function test_purchase_adds_stock_updates_cost_and_creates_debt(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stock' => 4, 'cost_price' => 1000, 'sale_price' => 1500]);

        $this->purchase($supplier, [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => '1200']], [
            'invoice_number' => '0001-00000123',
            'tax_amount' => '2520',
        ])->assertSessionHasNoErrors();

        $purchase = Purchase::sole();
        $this->assertEquals(12000, $purchase->subtotal);
        $this->assertEquals(14520, $purchase->total);

        $product->refresh();
        $this->assertSame(14, $product->stock);
        $this->assertEquals(1200, $product->cost_price);
        $this->assertEquals(1500, $product->sale_price, 'El precio de venta no se toca solo.');

        $movement = $product->stockMovements()->latest('id')->first();
        $this->assertSame(StockMovementType::Purchase, $movement->type);
        $this->assertSame(10, $movement->quantity);
        $this->assertTrue($movement->reference->is($purchase));

        $this->assertEquals(14520, $supplier->balance);
    }

    public function test_paying_at_purchase_time_reduces_the_debt(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['cost_price' => 100]);

        $this->purchase($supplier, [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => '100']], [
            'paid_now' => '400',
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(600, $supplier->balance);
    }

    public function test_cannot_pay_more_than_the_purchase_total(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $this->purchase($supplier, [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => '100']], [
            'paid_now' => '500',
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('paid_now');

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_repeated_products_are_rejected(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $this->purchase($supplier, [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => '100'],
            ['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => '100'],
        ])->assertSessionHasErrors('items.0.product_id');
    }

    public function test_price_review_only_for_changed_costs(): void
    {
        $supplier = Supplier::factory()->create();
        $same = Product::factory()->create(['cost_price' => 100, 'sale_price' => 150]);
        $changed = Product::factory()->create(['cost_price' => 100, 'sale_price' => 150]);

        $this->purchase($supplier, [
            ['product_id' => $same->id, 'quantity' => 1, 'unit_cost' => '100'],
            ['product_id' => $changed->id, 'quantity' => 1, 'unit_cost' => '120'],
        ]);

        $purchase = Purchase::sole();
        $pending = $purchase->items()->pendingPriceReview()->sole();
        $this->assertTrue($pending->product->is($changed));
        $this->assertEquals(180, $pending->suggested_price, 'Mantiene el 50% de margen sobre el costo nuevo.');

        $this->get(route('erp.purchases.show', $purchase))->assertOk()->assertSee('Revisá los precios');

        $this->post(route('erp.purchases.price-review', $pending), ['action' => 'apply', 'sale_price' => '185'])
            ->assertSessionHas('success');

        $this->assertEquals(185, $changed->refresh()->sale_price);
        $this->assertNotNull($pending->refresh()->price_reviewed_at);
        $this->get(route('erp.purchases.show', $purchase))->assertDontSee('Revisá los precios');
    }

    public function test_keeping_the_price_marks_it_as_reviewed(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['cost_price' => 100, 'sale_price' => 150]);
        $this->purchase($supplier, [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => '90']]);

        $item = Purchase::sole()->items()->sole();
        $this->post(route('erp.purchases.price-review', $item), ['action' => 'keep']);

        $this->assertEquals(150, $product->refresh()->sale_price);
        $this->assertNotNull($item->refresh()->price_reviewed_at);
    }

    public function test_supplier_payment_and_account_statement(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Hudson SA']);
        $product = Product::factory()->create();
        $this->purchase($supplier, [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => '200']]);

        $this->get(route('erp.suppliers.payments.create', $supplier))->assertOk();
        $this->post(route('erp.suppliers.payments.store', $supplier), [
            'amount' => '300', 'method' => 'transfer', 'date' => now()->toDateString(), 'note' => 'Transf. 4455',
        ])->assertRedirect(route('erp.suppliers.show', $supplier));

        $this->assertEquals(700, $supplier->balance);

        $this->get(route('erp.suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Pago · Transferencia · Transf. 4455')
            ->assertSee('$ 700,00');

        $this->get(route('erp.suppliers.index'))->assertOk()->assertSee('Hudson SA')->assertSee('le debés');
    }

    public function test_supplier_crud_normalizes_cuit_and_protects_history(): void
    {
        $this->post(route('erp.suppliers.store'), ['name' => 'Carol', 'cuit' => '30123456789'])->assertSessionHasNoErrors();
        $supplier = Supplier::firstWhere('name', 'Carol');
        $this->assertSame('30-12345678-9', $supplier->cuit);

        $this->post(route('erp.suppliers.store'), ['name' => 'Otro', 'cuit' => '30-12345678-9'])
            ->assertSessionHasErrors(['cuit' => 'Ya hay un proveedor con ese CUIT.']);

        $this->purchase($supplier, [['product_id' => Product::factory()->create()->id, 'quantity' => 1, 'unit_cost' => '10']]);
        $this->delete(route('erp.suppliers.destroy', $supplier))->assertSessionHas('error');
        $this->assertModelExists($supplier);
    }

    public function test_purchase_pages_render(): void
    {
        $supplier = Supplier::factory()->create();
        Product::factory()->create();

        $this->get(route('erp.purchases.index'))->assertOk();
        $this->get(route('erp.purchases.create', ['supplier' => $supplier->id]))->assertOk()->assertSee('Productos recibidos');
        $this->get(route('erp.suppliers.create'))->assertOk();
        $this->get(route('erp.suppliers.edit', $supplier))->assertOk();
    }
}
