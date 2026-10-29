<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Para la tienda online:
     *  - is_featured:   el corazón del ERP → aparece en "Lo más buscado" de la portada.
     *  - compare_price: precio "antes" que se muestra tachado (final, con IVA). Si es mayor
     *                   al precio actual, el producto lleva el cartel de OFERTA.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_published');
            $table->decimal('compare_price', 12, 2)->nullable()->after('tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'compare_price']);
        });
    }
};
