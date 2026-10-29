<x-layouts.app title="Inventario">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span> <span class="text-zinc-900">Inventario</span>
    </x-slot:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Inventario</h1>
            <p class="mt-1 text-sm text-zinc-500">Cada entrada y salida de mercadería queda registrada acá.</p>
        </div>
        <a href="{{ route('erp.inventory.create', ['return' => 'inventory']) }}" class="btn-primary">
            <x-icon name="plus" class="size-4" />
            Ajustar stock
        </a>
    </div>

    {{-- Indicadores --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-stat-card label="Unidades en stock" :value="number_format($stats['units'], 0, ',', '.')" icon="cube" tone="indigo" />
        <x-stat-card label="Valor a costo" :value="money($stats['cost_value'])" icon="banknotes" />
        <x-stat-card label="Valor a precio de venta" :value="money($stats['sale_value'])" icon="tag" tone="emerald" />
        <x-stat-card label="Stock bajo" :value="$stats['low_stock']" icon="warning" tone="amber"
                     :href="route('erp.products.index', ['stock' => 'low'])" />
        <x-stat-card label="Sin stock" :value="$stats['out_of_stock']" icon="x-circle" tone="rose"
                     :href="route('erp.products.index', ['stock' => 'out'])" />
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('erp.inventory.index') }}"
          class="mt-6 flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-3">
        @if ($product)
            <input type="hidden" name="product" value="{{ $product->id }}">
            <span class="inline-flex items-center gap-2 rounded-md bg-indigo-50 px-3 py-2 text-sm text-indigo-700">
                {{ $product->name }}
                <a href="{{ route('erp.inventory.index', collect($filters)->except('product')->all()) }}" class="text-indigo-400 hover:text-indigo-700" title="Quitar filtro">
                    <x-icon name="x" class="size-4" />
                </a>
            </span>
        @else
            <div class="relative min-w-56 flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar producto o SKU…" class="form-input pl-9">
            </div>
        @endif

        <select name="type" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Todos los movimientos</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>

        <label class="flex items-center gap-2 text-sm text-zinc-500">
            Desde <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-input w-auto" onchange="this.form.submit()">
        </label>
        <label class="flex items-center gap-2 text-sm text-zinc-500">
            Hasta <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-input w-auto" onchange="this.form.submit()">
        </label>

        <button type="submit" class="btn-secondary">Buscar</button>

        @if (collect($filters)->filter()->isNotEmpty())
            <a href="{{ route('erp.inventory.index') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-900">Limpiar filtros</a>
        @endif
    </form>

    {{-- Movimientos --}}
    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white">
        @if ($movements->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-400">
                    <x-icon name="archive" class="size-6" />
                </span>
                <h3 class="mt-4 font-medium">No hay movimientos para mostrar</h3>
                <p class="mt-1 text-sm text-zinc-500">Probá con otros filtros.</p>
            </div>
        @else
            @include('erp.inventory._movements', ['movements' => $movements, 'showProduct' => ! $product])

            @if ($movements->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3">{{ $movements->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.app>
