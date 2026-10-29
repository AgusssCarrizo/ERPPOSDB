{{--
    Layout de la tienda online. $store (configuración) y $menuCategories los comparte
    el View::composer de AppServiceProvider.
--}}
@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="es-AR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ? $title.' · ' : '' }}DecoBazar</title>
    <meta name="description" content="{{ $description ?? $store->about_text }}">

    @fonts(['playfair-display', 'jost'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-cream font-shop text-ink antialiased">

    {{-- Barra de anuncios --}}
    @if ($store->announcement)
        <div class="bg-ink px-4 py-2 text-center text-[10px] font-semibold tracking-[0.25em] text-white uppercase sm:text-[11px]">
            {{ $store->announcement }}
        </div>
    @endif

    {{-- Encabezado: búsqueda · logo · redes --}}
    <header class="sticky top-0 z-30 border-b border-ink/5 bg-cream/90 backdrop-blur">
        <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <form action="{{ route('shop.products.index') }}" method="GET" class="relative hidden max-w-xs sm:block">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-ink/40" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="¿Qué estás buscando?"
                       class="w-full rounded-full border border-transparent bg-white/70 py-2.5 pr-4 pl-10 text-sm placeholder:text-ink/40 focus:border-sand focus:bg-white focus:outline-none">
            </form>
            <span class="sm:hidden"></span>

            <a href="{{ route('shop.home') }}" class="flex flex-col items-center" title="DecoBazar">
                <img src="{{ asset('images/logo-decobazar.png') }}" alt="DecoBazar" class="size-10">
                <span class="mt-0.5 text-[10px] font-semibold tracking-[0.35em] uppercase">Decobazar</span>
            </a>

            <nav class="flex items-center justify-end gap-1">
                @if ($store->instagramUrl())
                    <a href="{{ $store->instagramUrl() }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full transition hover:bg-white" title="Instagram">
                        <x-icon name="instagram" />
                    </a>
                @endif
                @if ($store->whatsappUrl())
                    <a href="{{ $store->whatsappUrl() }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full transition hover:bg-white" title="WhatsApp">
                        <x-icon name="chat" />
                    </a>
                @endif
            </nav>
        </div>

        {{-- En el celular la búsqueda va debajo --}}
        <form action="{{ route('shop.products.index') }}" method="GET" class="px-4 pb-3 sm:hidden">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-ink/40" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="¿Qué estás buscando?"
                       class="w-full rounded-full bg-white/70 py-2.5 pr-4 pl-10 text-sm placeholder:text-ink/40 focus:bg-white focus:outline-none">
            </div>
        </form>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Pie --}}
    <footer class="mt-20 bg-ink text-white/70">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div>
                <a href="{{ route('shop.home') }}" class="flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-full bg-white">
                        <img src="{{ asset('images/logo-decobazar.png') }}" alt="" class="size-7">
                    </span>
                    <span class="text-xs font-semibold tracking-[0.35em] text-white uppercase">Decobazar</span>
                </a>
                @if ($store->about_text)
                    <p class="mt-5 max-w-xs text-sm leading-relaxed">{{ $store->about_text }}</p>
                @endif
                <div class="mt-6 flex gap-2">
                    @if ($store->instagramUrl())
                        <a href="{{ $store->instagramUrl() }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white hover:text-ink" title="Instagram">
                            <x-icon name="instagram" class="size-4" />
                        </a>
                    @endif
                    @if ($store->whatsappUrl())
                        <a href="{{ $store->whatsappUrl() }}" target="_blank" rel="noopener" class="grid size-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white hover:text-ink" title="WhatsApp">
                            <x-icon name="chat" class="size-4" />
                        </a>
                    @endif
                </div>
            </div>

            <div>
                <h3 class="text-[11px] font-semibold tracking-[0.3em] text-sand uppercase">Navegación</h3>
                <ul class="mt-5 space-y-3 text-sm">
                    @foreach ($menuCategories as $category)
                        <li><a href="{{ route('shop.categories.show', $category) }}" class="transition hover:text-white">{{ $category->name }}</a></li>
                    @endforeach
                    <li><a href="{{ route('shop.products.index') }}" class="transition hover:text-white">Todos los productos</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-[11px] font-semibold tracking-[0.3em] text-sand uppercase">Atención</h3>
                <ul class="mt-5 space-y-3 text-sm">
                    @if ($store->whatsappUrl())
                        <li><a href="{{ $store->whatsappUrl() }}" target="_blank" rel="noopener" class="transition hover:text-white">WhatsApp {{ $store->whatsapp }}</a></li>
                    @endif
                    @if ($store->instagramUrl())
                        <li><a href="{{ $store->instagramUrl() }}" target="_blank" rel="noopener" class="transition hover:text-white">Instagram {{ '@'.ltrim($store->instagram, '@') }}</a></li>
                    @endif
                    @foreach ($store->benefits as $benefit)
                        <li>{{ $benefit['title'] }}</li>
                    @endforeach
                </ul>
            </div>

            <div>
                @if ($store->address)
                    <h3 class="text-[11px] font-semibold tracking-[0.3em] text-sand uppercase">Ubicación</h3>
                    <p class="mt-5 flex gap-2 text-sm">
                        <x-icon name="map-pin" class="mt-0.5 size-4 text-white" />
                        {{ $store->address }}
                    </p>
                @endif

                @if ($store->whatsappUrl())
                    <h3 class="mt-8 text-[11px] font-semibold tracking-[0.3em] text-sand uppercase">¿Tenés dudas?</h3>
                    <a href="{{ $store->whatsappUrl() }}" target="_blank" rel="noopener"
                       class="mt-4 flex items-center justify-between gap-3 rounded-full bg-white/10 py-1.5 pr-1.5 pl-5 text-sm transition hover:bg-white/15">
                        Escribinos
                        <span class="rounded-full bg-white px-4 py-2 text-[10px] font-semibold tracking-[0.15em] text-ink uppercase">WhatsApp</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-[10px] font-semibold tracking-[0.2em] uppercase sm:flex-row sm:px-6 lg:px-8">
                <p>© {{ now()->year }} Decobazar. Todos los derechos reservados.</p>
                <p class="flex items-center gap-1.5">Hecho con <x-icon name="heart" class="size-3.5 fill-rose-500 text-rose-500" /> para tu hogar</p>
            </div>
        </div>
    </footer>
</body>
</html>
