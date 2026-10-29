<x-layouts.app :title="$product->name">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.products.index') }}" class="hover:text-zinc-900">Productos</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">{{ $product->name }}</span>
    </x-slot:breadcrumbs>

    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('erp.products.index') }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-semibold tracking-tight">{{ $product->name }}</h1>
                <p class="text-sm text-zinc-500">
                    <span class="font-mono">{{ $product->sku }}</span>
                    @if ($product->category) · {{ $product->category->name }} @endif
                    @if ($product->brand) · {{ $product->brand->name }} @endif
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            <form method="POST" action="{{ route('erp.products.destroy', $product) }}" onsubmit="return confirm('¿Eliminar este producto?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary text-rose-600 hover:bg-rose-50">
                    <x-icon name="trash" class="size-4" /> Eliminar
                </button>
            </form>
            <a href="{{ route('erp.products.edit', $product) }}" class="btn-primary">
                <x-icon name="pencil" class="size-4" /> Editar
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Imagen y descripción --}}
        <div class="space-y-6">
            <div class="card overflow-hidden">
                @if ($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
                @else
                    <div class="grid aspect-square place-items-center bg-zinc-100 text-zinc-400">
                        <x-icon name="photo" class="size-16" />
                    </div>
                @endif
            </div>

            <section class="card p-5">
                <h2 class="text-sm font-medium text-zinc-500">Descripción</h2>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-zinc-700">{{ $product->description ?: 'Sin descripción.' }}</p>
            </section>

            {{-- Historia de Instagram generada con la plantilla de resources/story/base.png --}}
            @php $storyUrl = route('erp.products.story', ['product' => $product, 'v' => $product->updated_at?->timestamp]); @endphp
            <section class="card">
                <header class="card-header flex items-center justify-between">
                    <h2 class="font-medium">Historia para Instagram</h2>
                    <span class="text-xs text-zinc-500">1080 × 1920</span>
                </header>
                <div class="p-5">
                    <a href="{{ $storyUrl }}" target="_blank" title="Ver en tamaño real">
                        <img src="{{ $storyUrl }}" alt="Historia de {{ $product->name }}" loading="lazy"
                             class="mx-auto aspect-9/16 w-full max-w-64 rounded-lg bg-zinc-100 object-cover ring-1 ring-zinc-200">
                    </a>
                    <a href="{{ route('erp.products.story', ['product' => $product, 'descargar' => 1]) }}" class="btn-primary mt-4 w-full">
                        <x-icon name="download" class="size-4" /> Descargar imagen
                    </a>
                    @unless ($product->image_path)
                        <p class="mt-3 text-xs text-amber-700">Este producto no tiene foto: la historia sale con "Sin imagen".</p>
                    @endunless
                </div>
            </section>
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Precios --}}
            <section class="card">
                <header class="card-header"><h2 class="font-medium">Precios</h2></header>
                <dl class="grid grid-cols-2 gap-px bg-zinc-100 sm:grid-cols-4">
                    <div class="bg-white p-5">
                        <dt class="text-xs text-zinc-500">Costo</dt>
                        <dd class="mt-1 text-lg font-semibold tabular-nums">{{ money($product->cost_price) }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs text-zinc-500">Margen sobre costo</dt>
                        <dd @class(['mt-1 text-lg font-semibold tabular-nums', 'text-rose-600' => $product->margin !== null && $product->margin < 15])>
                            {{ $product->margin === null ? '—' : percent($product->margin) }}
                        </dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs text-zinc-500">Venta (sin IVA)</dt>
                        <dd class="mt-1 text-lg font-semibold tabular-nums">{{ money($product->sale_price) }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-xs text-zinc-500">Precio final · IVA {{ percent($product->tax_rate) }}</dt>
                        <dd class="mt-1 text-lg font-semibold tabular-nums text-indigo-600">{{ money($product->final_price) }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Inventario --}}
            <section class="card">
                <header class="card-header flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <h2 class="font-medium">Inventario</h2>
                        <x-stock-badge :product="$product" />
                    </div>
                    <a href="{{ route('erp.inventory.create', ['product' => $product->id]) }}" class="btn-secondary px-3 py-1.5 text-xs">
                        <x-icon name="pencil" class="size-3.5" /> Ajustar stock
                    </a>
                </header>
                <dl class="divide-y divide-zinc-100 text-sm">
                    <div class="flex justify-between px-5 py-3">
                        <dt class="text-zinc-500">Stock actual</dt>
                        <dd class="font-medium tabular-nums">
                            {{ $product->stock }}
                            <span class="font-normal text-zinc-500">· {{ \App\Models\Product::UNITS[$product->unit] ?? $product->unit }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <dt class="text-zinc-500">Stock mínimo</dt>
                        <dd class="font-medium tabular-nums">{{ $product->min_stock }}</dd>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <dt class="text-zinc-500">Valor en stock (a costo)</dt>
                        <dd class="font-medium tabular-nums">{{ money(max(0, $product->stock) * $product->cost_price) }}</dd>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <dt class="text-zinc-500">Código de barras</dt>
                        <dd class="font-mono">{{ $product->barcode ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Últimos movimientos de stock --}}
            <section class="card overflow-hidden">
                <header class="card-header flex items-center justify-between">
                    <h2 class="font-medium">Movimientos de stock</h2>
                    @if ($movements->isNotEmpty())
                        <a href="{{ route('erp.inventory.index', ['product' => $product->id]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ver todos →</a>
                    @endif
                </header>
                @if ($movements->isEmpty())
                    <p class="px-5 py-6 text-center text-sm text-zinc-500">Todavía no tiene movimientos.</p>
                @else
                    @include('erp.inventory._movements', ['movements' => $movements, 'showProduct' => false])
                @endif
            </section>

            {{-- Canales --}}
            <section class="card">
                <header class="card-header"><h2 class="font-medium">Canales de venta</h2></header>
                <div class="grid gap-px bg-zinc-100 sm:grid-cols-2">
                    <div class="flex items-center gap-3 bg-white p-5">
                        <span @class(['grid size-10 place-items-center rounded-lg', 'bg-emerald-50 text-emerald-600' => $product->is_active, 'bg-zinc-100 text-zinc-400' => ! $product->is_active])>
                            <x-icon name="calculator" />
                        </span>
                        <div>
                            <p class="text-sm font-medium">Punto de venta</p>
                            <p class="text-xs text-zinc-500">{{ $product->is_active ? 'Disponible para vender' : 'Inactivo' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 bg-white p-5">
                        <span @class(['grid size-10 place-items-center rounded-lg', 'bg-emerald-50 text-emerald-600' => $product->is_published, 'bg-zinc-100 text-zinc-400' => ! $product->is_published])>
                            <x-icon name="globe" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium">Tienda online</p>
                            <p class="truncate text-xs text-zinc-500">
                                {{ $product->is_published ? 'Publicado en /producto/'.$product->slug : 'No publicado' }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <p class="text-xs text-zinc-500">
                Creado el {{ $product->created_at->format('d/m/Y H:i') }} · Última modificación {{ $product->updated_at->diffForHumans() }}
            </p>
        </div>
    </div>
</x-layouts.app>
