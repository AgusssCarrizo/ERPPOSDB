<x-layouts.shop :title="$product->name" :description="$product->description">
    <section class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8">
        <nav class="flex flex-wrap items-center gap-1.5 text-[11px] font-medium tracking-[0.15em] text-ink/50 uppercase">
            <a href="{{ route('shop.home') }}" class="hover:text-ink">Inicio</a>
            <x-icon name="chevron-right" class="size-3" />
            @if ($product->category)
                <a href="{{ route('shop.categories.show', $product->category) }}" class="hover:text-ink">{{ $product->category->name }}</a>
            @else
                <a href="{{ route('shop.products.index') }}" class="hover:text-ink">Productos</a>
            @endif
            <x-icon name="chevron-right" class="size-3" />
            <span class="truncate text-ink">{{ $product->name }}</span>
        </nav>

        <div class="mt-8 grid gap-10 lg:grid-cols-2 lg:gap-16">
            {{-- Foto --}}
            <div class="relative aspect-square overflow-hidden rounded-[2rem] bg-white">
                @if ($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="size-full object-cover">
                @else
                    <span class="grid size-full place-items-center bg-sand-light">
                        <img src="{{ asset('images/logo-decobazar.png') }}" alt="" class="size-24 opacity-20">
                    </span>
                @endif

                @if ($product->is_on_sale)
                    <span class="absolute top-6 left-6 rounded-full bg-sand px-4 py-1.5 text-[11px] font-semibold tracking-[0.15em] text-white uppercase">Oferta</span>
                @endif
            </div>

            {{-- Datos --}}
            <div class="lg:py-6">
                @if ($product->category)
                    <p class="shop-eyebrow">{{ $product->category->name }}</p>
                @endif
                <h1 class="mt-3 font-display text-4xl leading-tight sm:text-5xl">{{ $product->name }}</h1>

                <div class="mt-6 flex items-baseline gap-3">
                    <span class="text-3xl font-semibold">{{ shop_price($product->final_price) }}</span>
                    @if ($product->is_on_sale)
                        <span class="text-lg text-ink/40 line-through">{{ shop_price($product->compare_price) }}</span>
                    @endif
                </div>

                @if ($product->stock > 0)
                    <p class="mt-3 flex items-center gap-2 text-sm text-emerald-700">
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        {{ $product->stock <= $product->min_stock ? '¡Últimas unidades!' : 'Disponible' }}
                    </p>
                @else
                    <p class="mt-3 flex items-center gap-2 text-sm text-rose-700">
                        <span class="size-2 rounded-full bg-rose-500"></span>
                        Sin stock por ahora
                    </p>
                @endif

                @if ($product->description)
                    <p class="mt-8 text-sm leading-relaxed whitespace-pre-line text-ink/70">{{ $product->description }}</p>
                @endif

                <div class="mt-10 flex flex-wrap gap-3">
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="shop-btn">
                            <x-icon name="chat" class="size-4" />
                            {{ $product->stock > 0 ? 'Comprar por WhatsApp' : 'Consultar por WhatsApp' }}
                        </a>
                    @endif
                    <button type="button" x-data="favoriteButton({{ $product->id }})" @click="toggle()" class="shop-btn-outline cursor-pointer">
                        <x-icon name="heart" class="size-4" ::class="active && 'fill-rose-500 text-rose-500'" />
                        <span x-text="active ? 'En favoritos' : 'Favoritos'">Favoritos</span>
                    </button>
                </div>

                <ul class="mt-12 space-y-4 border-t border-ink/10 pt-8">
                    @foreach ($settings->benefits as $index => $benefit)
                        <li class="flex gap-4">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-sand shadow-sm">
                                <x-icon :name="\App\Models\StoreSetting::BENEFIT_ICONS[$index] ?? 'check'" class="size-5" />
                            </span>
                            <span>
                                <span class="block text-xs font-semibold tracking-[0.15em] uppercase">{{ $benefit['title'] }}</span>
                                <span class="block text-xs text-ink/50">{{ $benefit['text'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-24 sm:px-6 lg:px-8">
            <p class="shop-eyebrow">Para combinar</p>
            <h2 class="mt-3 font-display text-4xl">También te puede <span class="italic">gustar</span></h2>

            <div class="mt-10 grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4">
                @foreach ($related as $item)
                    <x-shop.product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.shop>
