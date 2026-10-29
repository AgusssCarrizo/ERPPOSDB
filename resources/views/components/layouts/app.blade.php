@props(['title' => null])

@php
    // Menú lateral. 'route' => null significa que el módulo todavía no existe.
    $navigation = [
        'ERP' => [
            ['label' => 'Panel', 'icon' => 'home', 'route' => null],
            ['label' => 'Productos', 'icon' => 'cube', 'route' => 'erp.products.index', 'active' => 'erp.products.*'],
            ['label' => 'Categorías', 'icon' => 'tag', 'route' => 'erp.categories.index', 'active' => 'erp.categories.*'],
            ['label' => 'Marcas', 'icon' => 'swatch', 'route' => null],
            ['label' => 'Inventario', 'icon' => 'archive', 'route' => 'erp.inventory.index', 'active' => 'erp.inventory.*'],
            ['label' => 'Compras', 'icon' => 'truck', 'route' => 'erp.purchases.index', 'active' => 'erp.purchases.*'],
            ['label' => 'Proveedores', 'icon' => 'users', 'route' => 'erp.suppliers.index', 'active' => 'erp.suppliers.*'],
            ['label' => 'Clientes', 'icon' => 'users', 'route' => null],
        ],
        'Punto de venta' => [
            ['label' => 'Vender', 'icon' => 'calculator', 'route' => 'pos.index'],
            ['label' => 'Ventas', 'icon' => 'receipt', 'route' => 'pos.sales.index', 'active' => 'pos.sales.*'],
            ['label' => 'Caja', 'icon' => 'banknotes', 'route' => null],
        ],
        'E-commerce' => [
            ['label' => 'Catálogo PDF', 'icon' => 'book', 'route' => 'erp.catalog.index', 'active' => 'erp.catalog.*'],
            ['label' => 'Tienda online', 'icon' => 'storefront', 'route' => 'erp.store.edit', 'active' => 'erp.store.*'],
            ['label' => 'Pedidos', 'icon' => 'shopping-bag', 'route' => null],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

    @fonts('instrument-sans')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-zinc-50 text-zinc-900 antialiased" x-data="{ sidebarOpen: false }">

    {{-- Fondo oscuro del menú en mobile --}}
    <div x-show="sidebarOpen" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-zinc-900/50 lg:hidden" @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-zinc-900 text-zinc-300 transition-transform lg:translate-x-0"
        :class="{ 'translate-x-0': sidebarOpen }"
    >
        <div class="flex h-16 items-center justify-between gap-2 px-5">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <span class="grid size-8 place-items-center rounded-lg bg-indigo-500 text-sm font-semibold text-white">B</span>
                <span class="font-semibold text-white">{{ config('app.name') }}</span>
            </a>
            <button type="button" class="rounded-md p-1 text-zinc-400 hover:text-white lg:hidden" @click="sidebarOpen = false">
                <x-icon name="x" />
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
            @foreach ($navigation as $group => $items)
                <div>
                    <p class="px-3 text-xs font-medium uppercase tracking-wider text-zinc-500">{{ $group }}</p>
                    <ul class="mt-2 space-y-0.5">
                        @foreach ($items as $item)
                            <li>
                                @if ($item['route'])
                                    @php $isActive = request()->routeIs($item['active'] ?? $item['route']); @endphp
                                    <a href="{{ route($item['route']) }}"
                                       @class([
                                           'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition',
                                           'bg-zinc-800 text-white' => $isActive,
                                           'hover:bg-zinc-800/60 hover:text-white' => ! $isActive,
                                       ])>
                                        <x-icon :name="$item['icon']" />
                                        {{ $item['label'] }}
                                    </a>
                                @else
                                    <span class="flex cursor-not-allowed items-center gap-3 rounded-md px-3 py-2 text-sm text-zinc-500" title="Próximamente">
                                        <x-icon :name="$item['icon']" />
                                        {{ $item['label'] }}
                                        <span class="ml-auto rounded bg-zinc-800 px-1.5 py-0.5 text-[10px] font-medium uppercase text-zinc-400">Pronto</span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="border-t border-zinc-800 p-4 text-xs text-zinc-500">
            v0.1 · Laravel {{ app()->version() }}
        </div>
    </aside>

    {{-- Contenido --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-zinc-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button type="button" class="-ml-1 rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100 lg:hidden" @click="sidebarOpen = true">
                <x-icon name="menu" class="size-6" />
                <span class="sr-only">Abrir menú</span>
            </button>

            <div class="min-w-0 flex-1 text-sm text-zinc-500">
                {{ $breadcrumbs ?? '' }}
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden text-sm text-zinc-600 sm:block">Administrador</span>
                <span class="grid size-8 place-items-center rounded-full bg-zinc-200 text-sm font-medium text-zinc-700">A</span>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                     class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <x-icon name="check" class="size-5 text-emerald-600" />
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 flex items-center gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <x-icon name="warning" class="size-5 text-rose-600" />
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
