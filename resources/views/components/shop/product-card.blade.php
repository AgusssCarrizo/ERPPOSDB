{{-- Tarjeta de producto de la tienda: foto, corazón de favoritos, OFERTA, nombre y precio. --}}
@props(['product'])

<article class="group relative">
    <a href="{{ route('shop.products.show', $product) }}" class="block">
        <div class="relative aspect-[4/5] overflow-hidden rounded-3xl bg-white">
            @if ($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
                     class="size-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <span class="grid size-full place-items-center bg-sand-light">
                    <img src="{{ asset('images/logo-decobazar.png') }}" alt="" class="size-14 opacity-20">
                </span>
            @endif

            @if ($product->is_on_sale)
                <span class="absolute top-4 left-4 rounded-full bg-sand px-3 py-1 text-[10px] font-semibold tracking-[0.15em] text-white uppercase">Oferta</span>
            @endif
        </div>

        <div class="mt-4 text-center">
            <h3 class="line-clamp-2 text-sm text-ink/80">{{ $product->name }}</h3>
            <p class="mt-1 flex items-baseline justify-center gap-2">
                <span class="font-semibold">{{ shop_price($product->final_price) }}</span>
                @if ($product->is_on_sale)
                    <span class="text-xs text-ink/40 line-through">{{ shop_price($product->compare_price) }}</span>
                @endif
            </p>
        </div>
    </a>

    {{-- Favoritos: quedan guardados en este navegador --}}
    <button type="button" x-data="favoriteButton({{ $product->id }})" @click="toggle()"
            class="absolute top-4 right-4 grid size-9 cursor-pointer place-items-center rounded-full bg-white/90 shadow-sm transition hover:scale-110"
            :title="active ? 'Quitar de favoritos' : 'Agregar a favoritos'">
        <x-icon name="heart" class="size-4" ::class="active ? 'fill-rose-500 text-rose-500' : 'text-ink'" />
    </button>
</article>
