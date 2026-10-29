<?php

namespace Tests\Feature;

use App\Enums\SalePaymentMethod;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function sell(array $items, array $payments, array $extra = []): TestResponse
    {
        return $this->postJson(route('pos.sales.store'), [
            'items' => $items,
            'payments' => $payments,
            ...$extra,
        ]);
    }

    /**
     * Precio 1000 sin IVA + 21% = 1210 final.
     */
    private function product(array $attributes = []): Product
    {
        return Product::factory()->create([
            'cost_price' => 600, 'sale_price' => 1000, 'tax_rate' => 21, 'stock' => 10, 'is_active' => true,
            ...$attributes,
        ]);
    }

    public function test_cash_sale_takes_stock_out_and_records_the_change(): void
    {
        $product = $this->product();

        $this->sell(
            [['product_id' => $product->id, 'quantity' => 2]],
            [['method' => 'cash', 'amount' => 2420, 'tendered' => 3000]],
        )->assertCreated()->assertJson(['number' => 'V-000001', 'total' => 2420, 'change' => 580, 'stocks' => [$product->id => 8]]);

        $sale = Sale::sole();
        $this->assertEquals(2420, $sale->total);
        $this->assertEquals(1210, $sale->items->sole()->unit_price);
        $this->assertSame(SalePaymentMethod::Cash, $sale->payments->sole()->method);

        $this->assertSame(8, $product->fresh()->stock);
        $movement = $product->stockMovements()->latest('id')->first();
        $this->assertSame(StockMovementType::Sale, $movement->type);
        $this->assertSame(-2, $movement->quantity);
        $this->assertTrue($movement->reference->is($sale));
    }

    public function test_payment_can_be_split_between_methods(): void
    {
        $product = $this->product();

        $this->sell(
            [['product_id' => $product->id, 'quantity' => 1]],
            [['method' => 'credit', 'amount' => 1000], ['method' => 'mercado_pago', 'amount' => 210]],
        )->assertCreated();

        $this->assertEqualsCanonicalizing(['credit', 'mercado_pago'], Sale::sole()->payments->pluck('method.value')->all());
    }

    public function test_discount_lowers_the_total(): void
    {
        $product = $this->product();

        $this->sell(
            [['product_id' => $product->id, 'quantity' => 1]],
            [['method' => 'debit', 'amount' => 1100]],
            ['discount' => 110],
        )->assertCreated();

        $sale = Sale::sole();
        $this->assertEquals(1210, $sale->subtotal);
        $this->assertEquals(110, $sale->discount);
        $this->assertEquals(1100, $sale->total);
    }

    public function test_payments_must_match_the_total(): void
    {
        $product = $this->product();

        $this->sell(
            [['product_id' => $product->id, 'quantity' => 1]],
            [['method' => 'cash', 'amount' => 1000]],
        )->assertUnprocessable()->assertJsonValidationErrors('payments');

        $this->assertSame(0, Sale::count());
        $this->assertSame(10, $product->fresh()->stock, 'Si la venta falla, el stock no se toca.');
    }

    public function test_cannot_sell_more_than_the_stock(): void
    {
        $product = $this->product(['name' => 'Mate de cerámica', 'stock' => 1]);

        $this->sell(
            [['product_id' => $product->id, 'quantity' => 2]],
            [['method' => 'cash', 'amount' => 2420]],
        )->assertUnprocessable()->assertJsonValidationErrors(['items.0.quantity' => 'Mate de cerámica: queda 1 en stock.']);

        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_inactive_products_cannot_be_sold(): void
    {
        $product = $this->product(['is_active' => false]);

        $this->sell(
            [['product_id' => $product->id, 'quantity' => 1]],
            [['method' => 'cash', 'amount' => 1210]],
        )->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');
    }

    public function test_cash_tendered_must_cover_the_amount(): void
    {
        $product = $this->product();

        $this->sell(
            [['product_id' => $product->id, 'quantity' => 1]],
            [['method' => 'cash', 'amount' => 1210, 'tendered' => 1000]],
        )->assertUnprocessable()->assertJsonValidationErrors('payments.0.tendered');
    }

    public function test_pos_sales_list_detail_and_ticket_render(): void
    {
        $product = $this->product(['name' => 'Taza de loza']);

        $this->get(route('pos.index'))->assertOk()->assertSee('Taza de loza');

        $this->sell(
            [['product_id' => $product->id, 'quantity' => 1]],
            [['method' => 'mercado_pago', 'amount' => 1210]],
        )->assertCreated();
        $sale = Sale::sole();

        $this->get(route('pos.sales.index'))->assertOk()->assertSee('V-000001')->assertSee('$ 1.210,00');
        $this->get(route('pos.sales.show', $sale))->assertOk()->assertSee('Taza de loza')->assertSee('Mercado Pago');
        $this->get(route('pos.sales.ticket', $sale))->assertOk()->assertSee('@decobazar.db')->assertSee('no válido como factura');
    }
}
