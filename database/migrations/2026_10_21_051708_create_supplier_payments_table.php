<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pagos a proveedores: descuentan deuda de su cuenta corriente.
     */
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();   // si se pagó junto con una compra
            $table->date('date');
            $table->decimal('amount', 14, 2);
            $table->string('method', 20);   // App\Enums\PaymentMethod
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
