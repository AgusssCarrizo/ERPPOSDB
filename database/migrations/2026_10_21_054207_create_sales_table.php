<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Una venta del mostrador (POS). La fecha de la venta es created_at.
     * Los importes ya tienen el IVA incluido: es lo que paga el cliente.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->decimal('subtotal', 14, 2);               // suma de los productos
            $table->decimal('discount', 14, 2)->default(0);   // descuento sobre el total, en pesos
            $table->decimal('total', 14, 2);                  // lo que se cobró
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
