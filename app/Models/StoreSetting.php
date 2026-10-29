<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'announcement',
    'hero_title', 'hero_highlight', 'hero_text', 'hero_button_label', 'hero_image_path',
    'promo_product_id', 'promo_eyebrow', 'promo_title', 'promo_highlight', 'promo_text',
    'promo_button_label', 'promo_badge_title', 'promo_badge_text',
    'benefits', 'about_text', 'address', 'instagram', 'whatsapp',
])]
class StoreSetting extends Model
{
    /**
     * Valores con los que arranca la tienda (los del diseño). Se cambian desde ERP → Tienda online.
     */
    public const DEFAULTS = [
        'announcement' => 'Envíos a todo el país • 3 cuotas sin interés en productos seleccionados',
        'hero_title' => 'Momentos que',
        'hero_highlight' => 'inspiran.',
        'hero_text' => 'Descubrí nuestra nueva colección de vajilla y organización diseñada para elevar cada rincón de tu hogar.',
        'hero_button_label' => 'Ver catálogo',
        'promo_eyebrow' => 'Especial momentos',
        'promo_title' => 'Combo Momento',
        'promo_highlight' => 'Gourmet.',
        'promo_text' => 'Hacé que tus mañanas sean un mimo diario.',
        'promo_button_label' => 'Quiero el mío',
        'promo_badge_title' => 'Súper vendido',
        'promo_badge_text' => 'Ideal para regalar o darte un gusto.',
        'benefits' => [
            ['title' => 'Envíos a todo el país', 'text' => 'Tu pedido llega seguro a cualquier rincón de Argentina.'],
            ['title' => 'Compra protegida', 'text' => 'Pagos 100% seguros con tarjetas, transferencia o QR.'],
            ['title' => 'Cambios fáciles', 'text' => 'Si no estás conforme, gestionamos tu cambio sin vueltas.'],
        ],
        'about_text' => 'Organización y bazar para tu hogar. Creamos espacios que inspiran bienestar y orden.',
        'address' => 'Int. Alvear, La Pampa, Argentina',
        'instagram' => 'decobazar.db',
        'whatsapp' => '+54 9 3388 458046',
    ];

    /**
     * Íconos fijos de los tres beneficios, en orden.
     */
    public const BENEFIT_ICONS = ['truck', 'shield-check', 'arrow-path'];

    /**
     * Foto de la portada si no se subió otra desde el ERP.
     */
    public const DEFAULT_HERO_IMAGE = 'images/imagen-momentos.jpg';

    protected function casts(): array
    {
        return [
            'benefits' => 'array',
        ];
    }

    /**
     * La única fila de configuración. Si todavía no existe, se crea con los valores del diseño.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], self::DEFAULTS);
    }

    public function promoProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'promo_product_id');
    }

    protected function heroImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hero_image_path
            ? Storage::disk('public')->url($this->hero_image_path)
            : asset(self::DEFAULT_HERO_IMAGE));
    }

    /**
     * Link para chatear: wa.me solo acepta dígitos (5493388458046).
     */
    public function whatsappUrl(?string $message = null): ?string
    {
        $number = preg_replace('/\D/', '', (string) $this->whatsapp);

        if ($number === '') {
            return null;
        }

        return 'https://wa.me/'.$number.($message ? '?text='.rawurlencode($message) : '');
    }

    public function instagramUrl(): ?string
    {
        $user = ltrim((string) $this->instagram, '@');

        return $user === '' ? null : "https://instagram.com/{$user}";
    }
}
