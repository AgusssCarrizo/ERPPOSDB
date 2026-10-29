<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Un mismo producto alimenta los tres módulos:
     *  - ERP:        costo, stock, stock mínimo, IVA.
     *  - POS:        código de barras, precio de venta, activo.
     *  - E-commerce: slug, descripción, imagen, publicado.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 64)->unique();
            $table->string('barcode', 64)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('unit', 16)->default('unidad');

            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(21);

            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);

            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
