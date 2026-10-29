<x-layouts.shop>
    {{-- Portada --}}
    <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-[2rem] bg-sand-light">
            @if ($settings->hero_image_url)
                <img src="{{ $settings->hero_image_url }}" alt="" class="absolute inset-0 size-full object-cover">
                {{-- Velo claro (a la izquierda; en el celular, entero) para que el texto se lea sobre cualquier foto --}}
                <div class="absolute inset-0 bg-white/75 sm:bg-transparent sm:bg-gradient-to-r sm:from-white/90 sm:via-white/60 sm:to-transparent"></div>
            @endif

            <div class="relative max-w-xl px-8 py-16 sm:px-14 sm:py-24">
                <h1 class="font-display text-5xl leading-[1.05] sm:text-6xl">
                    <span class="italic">{{ $settings->hero_title }}</span>
                    @if ($settings->hero_highlight)
                        <span class="block font-bold">{{ $settings->hero_highlight }}</span>
                    @endif
                </h1>
                @if ($settings->hero_text)
                    <p class="mt-6 max-w-sm text-sm leading-relaxed text-ink/70">{{ $settings->hero_text }}</p>
                @endif
                <a href="{{ route('shop.products.index') }}" class="shop-btn mt-8">
                    {{ $settings->hero_button_label }}
                    <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>
        </div>
    </section>

    {{-- Categorías --}}
    @if ($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-16 sm:px-6 lg:px-8">
            <ul class="flex snap-x justify-start gap-6 overflow-x-auto pb-2 sm:justify-center sm:gap-10">
                @foreach ($categories as $category)
                    <li class="shrink-0 snap-start">
                        <a href="{{ route('shop.categories.show', $category) }}" class="group flex w-24 flex-col items-center text-center sm:w-28">
                            <span class="size-24 overflow-hidden rounded-full bg-white p-1 ring-1 ring-ink/10 transition group-hover:ring-2 group-hover:ring-sand sm:size-28">
                                @if ($category->image_url)
                                    <img src="{{ $category->image_url }}" alt="" class="size-full rounded-full object-cover">
                                @else
                                    <span class="block size-full rounded-full bg-sand-light"></span>
                                @endif
                            </span>
                            <span class="mt-3 text-[10px] font-semibold tracking-[0.2em] uppercase transition group-hover:text-sand">{{ $category->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Lo más buscado: los productos marcados con ❤ en el ERP --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="shop-eyebrow">Favoritos de la semana</p>
                    <h2 class="mt-3 font-display text-4xl">Lo más <span class="italic">buscado</span></h2>
                </div>
                <a href="{{ route('shop.products.index') }}" class="shop-link shrink-0">Ver todo</a>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-x-5 gap-y-10 lg:grid-cols-4">
                @foreach ($featured as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Promoción (un producto o combo elegido en ERP → Tienda online) --}}
    @if ($promoProduct)
        <section class="mx-auto max-w-7xl px-4 pt-24 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 rounded-[2.5rem] bg-sand-light px-8 py-14 sm:px-14 lg:grid-cols-2 lg:py-20">
                <div>
                    @if ($settings->promo_eyebrow)
                        <p class="shop-eyebrow">{{ $settings->promo_eyebrow }}</p>
                    @endif
                    <h2 class="mt-5 font-display text-5xl leading-[1.05] sm:text-6xl">
                        <span class="italic">{{ $settings->promo_title ?: $promoProduct->name }}</span>
                        @if ($settings->promo_highlight)
                            <span class="block font-bold text-sand">{{ $settings->promo_highlight }}</span>
                        @endif
                    </h2>
                    @if ($settings->promo_text)
                        <p class="mt-6 max-w-sm text-sm leading-relaxed text-ink/70">{{ $settings->promo_text }}</p>
                    @endif

                    <div class="mt-8">
                        @if ($promoProduct->is_on_sale)
                            <p class="text-sm text-ink/40 line-through">{{ shop_price($promoProduct->compare_price) }}</p>
                        @endif
                        <p class="text-2xl font-semibold tracking-tight uppercase sm:text-3xl">
                            {{ $promoProduct->is_on_sale ? 'Precio especial' : 'Precio' }}
                            <span class="text-sand">{{ shop_price($promoProduct->final_price) }}</span>
                        </p>
                    </div>

                    <a href="{{ route('shop.products.show', $promoProduct) }}" class="shop-btn mt-8">{{ $settings->promo_button_label }}</a>
                </div>

                <div class="relative mx-auto w-full max-w-md lg:mr-0">
                    <div class="aspect-square overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-ink/10">
                        @if ($promoProduct->image_url)
                            <img src="{{ $promoProduct->image_url }}" alt="{{ $promoProduct->name }}" class="size-full object-cover">
                        @else
                            <span class="grid size-full place-items-center">
                                <img src="{{ asset('images/logo-decobazar.png') }}" alt="" class="size-20 opacity-20">
                            </span>
                        @endif
                    </div>

                    @if ($settings->promo_badge_title)
                        <div class="absolute -right-2 -bottom-6 max-w-52 rounded-2xl bg-white p-4 shadow-lg sm:-right-6">
                            <p class="flex items-center gap-1.5 text-[10px] font-semibold tracking-[0.15em] uppercase">
                                <x-icon name="star" class="size-4 fill-amber-400 text-amber-400" />
                                {{ $settings->promo_badge_title }}
                            </p>
                            @if ($settings->promo_badge_text)
                                <p class="mt-1.5 text-xs leading-snug text-ink/60">{{ $settings->promo_badge_text }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Beneficios --}}
    <section class="mx-auto max-w-7xl px-4 pt-24 sm:px-6 lg:px-8">
        <div class="grid divide-y divide-ink/10 border-y border-ink/10 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            @foreach ($settings->benefits as $index => $benefit)
                <div class="flex flex-col items-center px-6 py-10 text-center">
                    <span class="grid size-12 place-items-center rounded-xl bg-white text-sand shadow-sm">
                        <x-icon :name="\App\Models\StoreSetting::BENEFIT_ICONS[$index] ?? 'check'" />
                    </span>
                    <h3 class="mt-5 text-xs font-semibold tracking-[0.2em] uppercase">{{ $benefit['title'] }}</h3>
                    <p class="mt-2 max-w-xs text-xs text-ink/50">{{ $benefit['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.shop>
