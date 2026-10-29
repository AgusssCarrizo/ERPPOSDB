<x-layouts.app title="Categorías">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span> <span class="text-zinc-900">Categorías</span>
    </x-slot:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Categorías</h1>
            <p class="mt-1 text-sm text-zinc-500">Ordenan el catálogo. Cada una lleva una foto de portada.</p>
        </div>
        <a href="{{ route('erp.categories.create') }}" class="btn-primary">
            <x-icon name="plus" class="size-4" />
            Nueva categoría
        </a>
    </div>

    @if ($missingImages > 0)
        <div class="mt-6 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="warning" class="size-5 text-amber-600" />
            {{ $missingImages === 1 ? '1 categoría no tiene' : "{$missingImages} categorías no tienen" }} foto de portada.
            Cargala antes de generar el catálogo.
        </div>
    @endif

    @if ($categories->isEmpty())
        <div class="card mt-6 flex flex-col items-center px-6 py-16 text-center">
            <span class="grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-400">
                <x-icon name="tag" class="size-6" />
            </span>
            <h3 class="mt-4 font-medium">Todavía no hay categorías</h3>
            <p class="mt-1 text-sm text-zinc-500">Creá la primera con su foto de portada.</p>
            <a href="{{ route('erp.categories.create') }}" class="btn-primary mt-5">
                <x-icon name="plus" class="size-4" /> Nueva categoría
            </a>
        </div>
    @else
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($categories as $category)
                <article @class(['card flex flex-col overflow-hidden', 'opacity-60' => ! $category->is_active])>
                    <a href="{{ route('erp.categories.edit', $category) }}" class="relative block aspect-4/3 bg-zinc-100">
                        @if ($category->image_url)
                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="size-full object-cover">
                        @else
                            <span class="flex size-full flex-col items-center justify-center gap-2 border-b-2 border-dashed border-amber-300 bg-amber-50 text-amber-700">
                                <x-icon name="photo" class="size-8" />
                                <span class="text-xs font-medium">Falta la foto</span>
                            </span>
                        @endif
                        <span class="absolute top-2 left-2 rounded-md bg-white/90 px-1.5 py-0.5 text-xs font-medium text-zinc-600 tabular-nums shadow-xs" title="Orden en el catálogo">
                            #{{ $category->position }}
                        </span>
                        @unless ($category->is_active)
                            <span class="absolute top-2 right-2 rounded-md bg-zinc-900/80 px-1.5 py-0.5 text-xs font-medium text-white">Inactiva</span>
                        @endunless
                    </a>

                    <div class="flex flex-1 items-start justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <h2 class="truncate font-medium">{{ $category->name }}</h2>
                            <a href="{{ route('erp.products.index', ['category' => $category->id]) }}" class="text-sm text-zinc-500 hover:text-indigo-600">
                                {{ $category->products_count }} {{ $category->products_count === 1 ? 'producto' : 'productos' }}
                            </a>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <a href="{{ route('erp.categories.edit', $category) }}" class="icon-btn" title="Editar">
                                <x-icon name="pencil" class="size-4" />
                            </a>
                            <form method="POST" action="{{ route('erp.categories.destroy', $category) }}"
                                  onsubmit="return confirm(@js($category->products_count
                                      ? "¿Eliminar \"{$category->name}\"? Sus {$category->products_count} productos quedan sin categoría."
                                      : "¿Eliminar \"{$category->name}\"?"))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-btn hover:bg-rose-50 hover:text-rose-600" title="Eliminar">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.app>
