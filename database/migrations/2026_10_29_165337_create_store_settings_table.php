<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Textos e imágenes de la tienda online que se editan desde ERP → Tienda online.
     * Hay una sola fila (ver StoreSetting::current()).
     */
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();

            // Barra negra de arriba de todo
            $table->string('announcement')->nullable();

            // Portada: "Momentos que *inspiran.*"
            $table->string('hero_title');
            $table->string('hero_highlight')->nullable();
            $table->text('hero_text')->nullable();
            $table->string('hero_button_label');
            $table->string('hero_image_path')->nullable();

            // Bloque de promoción: un producto (puede ser un combo) con su precio especial
            $table->foreignId('promo_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('promo_eyebrow')->nullable();
            $table->string('promo_title')->nullable();
            $table->string('promo_highlight')->nullable();
            $table->text('promo_text')->nullable();
            $table->string('promo_button_label');
            $table->string('promo_badge_title')->nullable();
            $table->string('promo_badge_text')->nullable();

            // Tres beneficios (envíos, compra protegida, cambios): [{title, text}, ...]
            $table->json('benefits');

            // Pie y contacto
            $table->text('about_text')->nullable();
            $table->string('address')->nullable();
            $table->string('instagram')->nullable();
            $table->string('whatsapp')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
