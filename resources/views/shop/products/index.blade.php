{{-- Listado de la tienda: todos los productos, una búsqueda o una categoría ($category). --}}
<x-layouts.shop :title="$title">
    <section class="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-1.5 text-[11px] font-medium tracking-[0.15em] text-ink/50 uppercase">
            <a href="{{ route('shop.home') }}" class="hover:text-ink">Inicio</a>
            <x-icon name="chevron-right" class="size-3" />
            <span class="text-ink">{{ $category?->name ?? 'Productos' }}</span>
        </nav>

        <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="shop-eyebrow">{{ $eyebrow }}</p>
                <h1 class="mt-3 font-display text-4xl sm:text-5xl">{{ $title }}</h1>
                <p class="mt-2 text-sm text-ink/50">
                    {{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }}
                </p>
            </div>

            <form method="GET" class="flex items-center gap-2 text-sm">
                @if (request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                <label for="orden" class="text-ink/50">Ordenar por</label>
                <select id="orden" name="orden" onchange="this.form.submit()"
                        class="cursor-pointer rounded-full border border-ink/10 bg-white py-2 pr-8 pl-4 focus:border-sand focus:outline-none">
                    @foreach (\App\Http\Controllers\Shop\ProductController::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected(request('orden', 'recent') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($products->isEmpty())
            <div class="mt-12 rounded-[2rem] bg-white px-6 py-20 text-center">
                <p class="font-display text-2xl italic">No encontramos productos</p>
                <p class="mt-2 text-sm text-ink/50">Probá con otra búsqueda o mirá todo el catálogo.</p>
                <a href="{{ route('shop.products.index') }}" class="shop-btn mt-8">Ver todo</a>
            </div>
        @else
            <div class="mt-10 grid grid-cols-2 gap-x-5 gap-y-10 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>

            @if ($products->hasPages())
                <div class="mt-14">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </section>
</x-layouts.shop>
