<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Models\Concerns\HasSlug;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'category_id', 'brand_id', 'name', 'slug', 'sku', 'barcode', 'description', 'unit',
    'cost_price', 'sale_price', 'tax_rate', 'compare_price', 'stock', 'min_stock',
    'image_path', 'is_active', 'is_published', 'is_featured',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasSlug, SoftDeletes;

    public const UNITS = [
        'unidad' => 'Unidad',
        'caja' => 'Caja',
        'pack' => 'Pack',
        'kg' => 'Kilogramo',
        'litro' => 'Litro',
        'metro' => 'Metro',
    ];

    /**
     * Alícuotas de IVA sugeridas en el formulario. También se puede cargar cualquier otra a mano.
     */
    public const TAX_RATES = [
        '0' => 'Exento (0%)',
        '10.5' => '10,5%',
        '21' => '21%',
        '27' => '27%',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'stock' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * El stock con el que se crea el producto queda registrado como "Stock inicial".
     * Después, el stock solo cambia a través de App\Services\Inventory.
     */
    protected static function booted(): void
    {
        static::created(function (Product $product) {
            if ($product->stock !== 0) {
                $product->stockMovements()->create([
                    'type' => StockMovementType::Initial,
                    'quantity' => $product->stock,
                    'stock_after' => $product->stock,
                    'unit_cost' => $product->cost_price,
                ]);
            }
        });
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Productos con stock positivo pero en o por debajo del mínimo.
     */
    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'min_stock');
    }

    #[Scope]
    protected function outOfStock(Builder $query): void
    {
        $query->where('stock', '<=', 0);
    }

    /**
     * Lo que se puede ver en la tienda online: publicado, activo y de una categoría activa (o sin categoría).
     * El stock no se filtra acá: la ficha de un producto agotado se sigue viendo (ver availableInStore()).
     */
    #[Scope]
    protected function publishedInStore(Builder $query): void
    {
        $query->where('is_published', true)
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('category_id')
                    ->orWhereHas('category', fn (Builder $query) => $query->where('is_active', true));
            });
    }

    /**
     * Lo que se lista en la tienda (portada, categorías, búsqueda): como el catálogo, solo con stock.
     */
    #[Scope]
    protected function availableInStore(Builder $query): void
    {
        $query->publishedInStore()->where('stock', '>', 0);
    }

    /**
     * Aplica los filtros del listado (búsqueda, categoría, estado, stock, canal).
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                $query->where(function (Builder $query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%")
                        ->orWhere('barcode', 'like', "%{$term}%");
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, $id) => $query->where('category_id', $id))
            ->when($filters['brand'] ?? null, fn (Builder $query, $id) => $query->where('brand_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('is_active', $status === 'active'))
            ->when(($filters['stock'] ?? null) === 'low', fn (Builder $query) => $query->lowStock())
            ->when(($filters['stock'] ?? null) === 'out', fn (Builder $query) => $query->outOfStock())
            ->when(($filters['channel'] ?? null) === 'web', fn (Builder $query) => $query->where('is_published', true))
            ->when(($filters['channel'] ?? null) === 'pos', fn (Builder $query) => $query->where('is_active', true))
            ->when(($filters['channel'] ?? null) === 'featured', fn (Builder $query) => $query->where('is_featured', true));
    }

    /**
     * Precio final con IVA incluido.
     */
    protected function finalPrice(): Attribute
    {
        return Attribute::get(fn () => round($this->sale_price * (1 + $this->tax_rate / 100), 2));
    }

    /**
     * Está en oferta si tiene un precio "antes" mayor al precio final actual.
     */
    protected function isOnSale(): Attribute
    {
        return Attribute::get(fn () => $this->compare_price !== null && (float) $this->compare_price > $this->final_price);
    }

    /**
     * Margen sobre el costo, en porcentaje: costo 1000 y venta 1500 = 50%.
     */
    protected function margin(): Attribute
    {
        return Attribute::get(function () {
            if ($this->cost_price <= 0) {
                return null;
            }

            return round(($this->sale_price - $this->cost_price) / $this->cost_price * 100, 1);
        });
    }

    /**
     * 'out', 'low' u 'ok' según el stock actual.
     */
    protected function stockStatus(): Attribute
    {
        return Attribute::get(fn () => match (true) {
            $this->stock <= 0 => 'out',
            $this->stock <= $this->min_stock => 'low',
            default => 'ok',
        });
    }

    /**
     * Precios ya formateados. Lo usan la fila del listado y la edición rápida (JSON).
     */
    public function pricingSummary(): array
    {
        return [
            'has_cost' => $this->cost_price > 0,
            'margin' => $this->margin,
            'margin_label' => $this->margin === null ? '—' : percent($this->margin),
            'tax_rate' => (float) $this->tax_rate,
            'tax_label' => percent($this->tax_rate),
            'sale_price_label' => money($this->sale_price),
            'final_price_label' => money($this->final_price),
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null);
    }
}
