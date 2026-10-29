<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cada cambio de stock queda registrado acá. products.stock pasa a ser
     * el resultado de estos movimientos (se actualiza en App\Services\Inventory).
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                 // App\Enums\StockMovementType
            $table->string('reason', 30)->nullable();   // App\Enums\AdjustmentReason (solo ajustes)
            $table->integer('quantity');                // positivo = entra, negativo = sale
            $table->integer('stock_after');             // cómo quedó el stock después del movimiento
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('note')->nullable();
            $table->nullableMorphs('reference');        // compra, venta, etc. que lo generó
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        // El stock que ya tenían los productos pasa a ser su "stock inicial".
        DB::table('products')->where('stock', '!=', 0)->orderBy('id')->each(function ($product) {
            DB::table('stock_movements')->insert([
                'product_id' => $product->id,
                'type' => 'initial',
                'quantity' => $product->stock,
                'stock_after' => $product->stock,
                'unit_cost' => $product->cost_price,
                'created_at' => $product->created_at,
                'updated_at' => $product->created_at,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
