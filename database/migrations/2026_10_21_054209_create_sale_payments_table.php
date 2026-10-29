<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cómo se cobró una venta. Puede haber más de un pago (ej.: parte en efectivo y parte con tarjeta).
     */
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20);                       // App\Enums\SalePaymentMethod
            $table->decimal('amount', 14, 2);                   // lo que se aplicó a la venta
            $table->decimal('tendered', 14, 2)->nullable();     // solo efectivo: con cuánto pagó (para el vuelto)
            $table->timestamps();

            $table->index('method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
