<x-layouts.app title="Productos">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span> <span class="text-zinc-900">Productos</span>
    </x-slot:breadcrumbs>

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Productos</h1>
            <p class="mt-1 text-sm text-zinc-500">Catálogo único para ERP, punto de venta y tienda online.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('erp.products.import') }}" class="btn-secondary">
                <x-icon name="upload" class="size-4" />
                Importar Excel
            </a>
            <a href="{{ route('erp.products.create') }}" class="btn-primary">
                <x-icon name="plus" class="size-4" />
                Nuevo producto
            </a>
        </div>
    </div>

    {{-- Indicadores --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-stat-card label="Productos" :value="number_format($stats['total'], 0, ',', '.')" icon="cube" tone="indigo"
                     :href="route('erp.products.index')" />
        <x-stat-card label="Publicados en tienda" :value="number_format($stats['published'], 0, ',', '.')" icon="globe" tone="emerald"
                     :href="route('erp.products.index', ['channel' => 'web'])" />
        <x-stat-card label="Stock bajo" :value="$stats['low_stock']" icon="warning" tone="amber"
                     :href="route('erp.products.index', ['stock' => 'low'])" />
        <x-stat-card label="Sin stock" :value="$stats['out_of_stock']" icon="x-circle" tone="rose"
                     :href="route('erp.products.index', ['stock' => 'out'])" />
        <x-stat-card label="Valor del inventario (costo)" :value="money($stats['inventory_value'])" icon="banknotes"
                     class="col-span-2 lg:col-span-1" />
    </div>

    {{-- Filtros: cada select envía el formulario al cambiar --}}
    <form method="GET" action="{{ route('erp.products.index') }}"
          class="mt-6 flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-3">
        <div class="relative min-w-56 flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar por nombre, SKU o código de barras…"
                   class="form-input pl-9">
        </div>

        <select name="category" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>

        <select name="brand" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Todas las marcas</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected(($filters['brand'] ?? '') == $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>

        <select name="stock" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Cualquier stock</option>
            <option value="low" @selected(($filters['stock'] ?? '') === 'low')>Stock bajo</option>
            <option value="out" @selected(($filters['stock'] ?? '') === 'out')>Sin stock</option>
        </select>

        <select name="channel" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Todos los canales</option>
            <option value="pos" @selected(($filters['channel'] ?? '') === 'pos')>Punto de venta</option>
            <option value="web" @selected(($filters['channel'] ?? '') === 'web')>Tienda online</option>
            <option value="featured" @selected(($filters['channel'] ?? '') === 'featured')>Destacados ❤</option>
        </select>

        <select name="status" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Activos e inactivos</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Solo activos</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Solo inactivos</option>
        </select>

        <select name="sort" class="form-input w-auto" onchange="this.form.submit()">
            <option value="recent" @selected(($filters['sort'] ?? 'recent') === 'recent')>Más recientes</option>
            <option value="name" @selected(($filters['sort'] ?? '') === 'name')>Nombre (A–Z)</option>
            <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Precio: menor a mayor</option>
            <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Precio: mayor a menor</option>
            <option value="stock" @selected(($filters['sort'] ?? '') === 'stock')>Menor stock primero</option>
        </select>

        <button type="submit" class="btn-secondary">Buscar</button>

        @if (collect($filters)->except('sort')->filter()->isNotEmpty())
            <a href="{{ route('erp.products.index') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-900">Limpiar filtros</a>
        @endif
    </form>

    {{-- Tabla --}}
    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white">
        @if ($products->isEmpty())
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <span class="grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-400">
                    <x-icon name="cube" class="size-6" />
                </span>
                <h3 class="mt-4 font-medium">No hay productos para mostrar</h3>
                <p class="mt-1 text-sm text-zinc-500">Probá con otros filtros o cargá tu primer producto.</p>
                <a href="{{ route('erp.products.create') }}" class="btn-primary mt-5">
                    <x-icon name="plus" class="size-4" /> Nuevo producto
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Categoría</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3 text-right" title="Margen sobre costo · doble click para editar">Margen</th>
                            <th class="px-4 py-3 text-right" title="Doble click para editar">IVA</th>
                            <th class="px-4 py-3 text-right">Precio</th>
                            <th class="px-4 py-3">Canales</th>
                            <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($products as $product)
                            <tr x-data="productRow(@js(route('erp.products.pricing', $product)), @js($product->pricingSummary()))"
                                :class="saved ? 'bg-emerald-50' : 'hover:bg-zinc-50/70'"
                                @class(['transition-colors duration-500', 'opacity-60' => ! $product->is_active])>
                                <td class="px-4 py-3">
                                    <a href="{{ route('erp.products.show', $product) }}" class="flex items-center gap-3">
                                        @if ($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="" class="size-10 shrink-0 rounded-lg object-cover ring-1 ring-zinc-200">
                                        @else
                                            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-zinc-100 text-zinc-400">
                                                <x-icon name="photo" class="size-5" />
                                            </span>
                                        @endif
                                        <span class="min-w-0">
                                            <span class="block max-w-xs truncate font-medium text-zinc-900 hover:text-indigo-600">{{ $product->name }}</span>
                                            <span class="block font-mono text-xs text-zinc-500">{{ $product->sku }}</span>
                                        </span>
                                    </a>
                                </td>
                                {{-- Categoría: se guarda al elegirla en el desplegable --}}
                                <td class="px-4 py-3 whitespace-nowrap"
                                    x-data="quickField(@js(route('erp.products.quick-update', $product)), 'category_id', @js($product->category_id))">
                                    <select @change="save($event.target.value ? Number($event.target.value) : null)" :value="value ?? ''"
                                            class="quick-select" :class="{ 'quick-select-saved': saved, 'opacity-50': saving, 'inline-input-error': error }"
                                            aria-label="Categoría">
                                        <option value="">Sin categoría</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <p x-show="error" x-text="error" x-cloak class="inline-error ml-0"></p>
                                    <span class="block text-xs text-zinc-500">{{ $product->brand?->name }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <x-stock-badge :product="$product" />
                                </td>
                                {{-- Margen: doble click para editar (recalcula el precio) --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums" @dblclick="edit('margin')">
                                    <span x-show="editing !== 'margin'"
                                          x-text="pricing.margin_label"
                                          :title="pricing.has_cost ? 'Doble click para editar' : 'Cargá el costo para poder editar el margen'"
                                          :class="{
                                              'editable': pricing.has_cost,
                                              'text-zinc-400': pricing.margin === null,
                                              'text-rose-600': pricing.margin !== null && pricing.margin < 15,
                                              'text-zinc-700': pricing.margin !== null && pricing.margin >= 15,
                                          }"
                                          class="font-medium">{{ $product->pricingSummary()['margin_label'] }}</span>
                                    <span x-show="editing === 'margin'" x-cloak class="relative inline-block">
                                        <input type="number" step="0.01" x-ref="margin" x-model="draft"
                                               @keydown.enter.prevent="save()" @keydown.escape.prevent="cancel()" @blur="save()"
                                               class="form-input inline-input" :class="{ 'inline-input-error': error, 'opacity-50': saving }"
                                               aria-label="Margen sobre costo">
                                        <span class="input-suffix right-2">%</span>
                                    </span>
                                    <p x-show="editing === 'margin' && error" x-text="error" x-cloak class="inline-error"></p>
                                </td>

                                {{-- IVA: doble click para editar (sugiere las alícuotas comunes, acepta cualquiera) --}}
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums" @dblclick="edit('tax_rate')">
                                    <span x-show="editing !== 'tax_rate'" x-text="pricing.tax_label"
                                          class="editable text-zinc-700" title="Doble click para editar">{{ percent($product->tax_rate) }}</span>
                                    <span x-show="editing === 'tax_rate'" x-cloak class="relative inline-block">
                                        <input type="number" step="0.01" min="0" max="100" list="tax-presets" x-ref="tax_rate" x-model="draft"
                                               @keydown.enter.prevent="save()" @keydown.escape.prevent="cancel()" @blur="save()"
                                               class="form-input inline-input" :class="{ 'inline-input-error': error, 'opacity-50': saving }"
                                               aria-label="IVA">
                                        <span class="input-suffix right-2">%</span>
                                    </span>
                                    <p x-show="editing === 'tax_rate' && error" x-text="error" x-cloak class="inline-error"></p>
                                </td>

                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">
                                    <span class="font-medium" x-text="pricing.final_price_label">{{ money($product->final_price) }}</span>
                                    <span class="block text-xs text-zinc-500"><span x-text="pricing.sale_price_label">{{ money($product->sale_price) }}</span> + IVA</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-1.5">
                                        <span @class(['channel-pill', 'channel-pill-on' => $product->is_active]) title="Punto de venta">
                                            <x-icon name="calculator" class="size-3.5" /> POS
                                        </span>
                                        {{-- Web: click para publicar / despublicar en la tienda --}}
                                        <button type="button"
                                                x-data="quickField(@js(route('erp.products.quick-update', $product)), 'is_published', @js($product->is_published))"
                                                @click="save(!value)" :disabled="saving"
                                                class="channel-pill cursor-pointer hover:ring-1 hover:ring-indigo-300"
                                                :class="{ 'channel-pill-on': value, 'opacity-50': saving, 'ring-2 ring-emerald-400': saved }"
                                                :title="value ? 'Publicado en la tienda (click para sacarlo)' : 'No está en la tienda (click para publicarlo)'">
                                            <x-icon name="globe" class="size-3.5" /> Web
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        {{-- Corazón: destacado en "Lo más buscado" de la tienda --}}
                                        <button type="button" x-data="featuredToggle(@js(route('erp.products.featured', $product)), @js($product->is_featured))"
                                                @click="toggle()" class="icon-btn hover:bg-rose-50 hover:text-rose-500"
                                                :title="featured ? 'Destacado en la tienda (click para quitar)' : 'Destacar en la tienda'">
                                            <x-icon name="heart" class="size-4" ::class="featured && 'fill-rose-500 text-rose-500'" />
                                        </button>
                                        <a href="{{ route('erp.products.show', $product) }}" class="icon-btn" title="Ver">
                                            <x-icon name="eye" class="size-4" />
                                        </a>
                                        <a href="{{ route('erp.products.story', ['product' => $product, 'descargar' => 1]) }}" class="icon-btn" title="Descargar historia de Instagram">
                                            <x-icon name="download" class="size-4" />
                                        </a>
                                        <a href="{{ route('erp.products.edit', $product) }}" class="icon-btn" title="Editar">
                                            <x-icon name="pencil" class="size-4" />
                                        </a>
                                        <form method="POST" action="{{ route('erp.products.destroy', $product) }}"
                                              onsubmit="return confirm('¿Eliminar {{ addslashes($product->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn hover:bg-rose-50 hover:text-rose-600" title="Eliminar">
                                                <x-icon name="trash" class="size-4" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Sugerencias para la edición rápida del IVA --}}
                <datalist id="tax-presets">
                    @foreach (array_keys(\App\Models\Product::TAX_RATES) as $rate)
                        <option value="{{ $rate }}"></option>
                    @endforeach
                </datalist>
            </div>

            @if ($products->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.app>
