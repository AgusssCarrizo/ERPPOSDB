<x-layouts.app title="Catálogo PDF">
    <x-slot:breadcrumbs>
        <span>E-commerce</span> <span class="mx-1.5 text-zinc-300">/</span> <span class="text-zinc-900">Catálogo PDF</span>
    </x-slot:breadcrumbs>

    @php
        $perPage = \App\Catalog\Sections\CategorySection::PRODUCTS_PER_PAGE;
        $selectable = $categories->where('catalog_products_count', '>', 0);
    @endphp

    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Catálogo PDF</h1>
        <p class="mt-1 text-sm text-zinc-500">
            Armá el catálogo por secciones. Entran los productos activos con stock, {{ $perPage }} por página, con precio final (IVA incluido).
        </p>
    </div>

    <form method="GET" action="{{ route('erp.catalog.pdf') }}"
          x-data="catalogBuilder(
              @js($categories->map(fn ($c) => ['id' => $c->id, 'count' => $c->catalog_products_count])->values()),
              @js(array_map('strval', old('categories', $selectable->pluck('id')->all()))),
              {{ $perPage }},
          )"
          class="mt-6 grid gap-6 lg:grid-cols-3">

        {{-- Secciones --}}
        <ol class="space-y-6 lg:col-span-2">

            {{-- 1. Portada --}}
            <li class="card">
                <header class="card-header flex items-center gap-3">
                    <span class="grid size-7 place-items-center rounded-full bg-indigo-50 text-sm font-semibold text-indigo-600">1</span>
                    <div>
                        <h2 class="font-medium">{{ \App\Catalog\SectionType::Cover->label() }}</h2>
                        <p class="text-sm text-zinc-500">{{ \App\Catalog\SectionType::Cover->description() }}</p>
                    </div>
                </header>
                <div class="grid gap-5 p-5 sm:grid-cols-2">
                    <x-form.field label="Título" for="title" required>
                        <input type="text" id="title" name="title" maxlength="40" value="{{ old('title', 'Catálogo') }}" class="form-input" required>
                    </x-form.field>
                    <x-form.field label="Subtítulo" for="subtitle" hint="Por ejemplo la temporada o el mes.">
                        <input type="text" id="subtitle" name="subtitle" maxlength="40" value="{{ old('subtitle', $defaultSubtitle) }}" class="form-input">
                    </x-form.field>
                </div>
            </li>

            {{-- 2. Categorías --}}
            <li class="card">
                <header class="card-header flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid size-7 place-items-center rounded-full bg-indigo-50 text-sm font-semibold text-indigo-600">2</span>
                        <div>
                            <h2 class="font-medium">{{ \App\Catalog\SectionType::Categories->label() }}</h2>
                            <p class="text-sm text-zinc-500">{{ \App\Catalog\SectionType::Categories->description() }}</p>
                        </div>
                    </div>
                    <div class="flex gap-3 text-sm font-medium">
                        <button type="button" @click="selectAll" class="text-indigo-600 hover:text-indigo-500">Todas</button>
                        <button type="button" @click="selectNone" class="text-zinc-500 hover:text-zinc-900">Ninguna</button>
                    </div>
                </header>

                @error('categories')
                    <p class="border-b border-rose-100 bg-rose-50 px-5 py-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror

                <ul class="divide-y divide-zinc-100">
                    @forelse ($categories as $category)
                        @php $empty = $category->catalog_products_count === 0; @endphp
                        <li>
                            <label @class(['flex items-center gap-4 px-5 py-3', 'cursor-pointer hover:bg-zinc-50' => ! $empty, 'cursor-not-allowed opacity-50' => $empty])>
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}" x-model="selected" @disabled($empty)
                                       class="size-4 rounded border-zinc-300 text-indigo-600 accent-indigo-600">

                                @if ($category->image_url)
                                    <img src="{{ $category->image_url }}" alt="" class="size-12 shrink-0 rounded-md object-cover ring-1 ring-zinc-200">
                                @else
                                    <span class="grid size-12 shrink-0 place-items-center rounded-md bg-amber-50 text-amber-600 ring-1 ring-amber-200" title="Sin foto de portada">
                                        <x-icon name="photo" class="size-5" />
                                    </span>
                                @endif

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium">{{ $category->name }}</span>
                                    <span class="block text-xs text-zinc-500">
                                        @if ($empty)
                                            Sin productos con stock
                                        @else
                                            {{ $category->catalog_products_count }} {{ $category->catalog_products_count === 1 ? 'producto' : 'productos' }} con stock
                                            · {{ 1 + (int) ceil($category->catalog_products_count / $perPage) }} págs.
                                        @endif
                                    </span>
                                </span>

                                @unless ($category->image_path)
                                    <a href="{{ route('erp.categories.edit', $category) }}" class="shrink-0 text-xs font-medium text-amber-700 hover:text-amber-800">
                                        Falta la foto →
                                    </a>
                                @endunless
                            </label>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-zinc-500">
                            No hay categorías activas. <a href="{{ route('erp.categories.create') }}" class="font-medium text-indigo-600">Crear una</a>
                        </li>
                    @endforelse
                </ul>

                @if ($uncategorized > 0)
                    <p class="border-t border-zinc-100 px-5 py-3 text-xs text-zinc-500">
                        {{ $uncategorized }} {{ $uncategorized === 1 ? 'producto con stock no tiene' : 'productos con stock no tienen' }} categoría y no entra{{ $uncategorized === 1 ? '' : 'n' }} al catálogo.
                    </p>
                @endif
            </li>

            {{-- Agregar sección: tipos futuros (ej. descuentos) --}}
            <li x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" @click.outside="open = false"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 px-4 py-4 text-sm font-medium text-zinc-600 transition hover:border-indigo-400 hover:text-indigo-600">
                    <x-icon name="plus" class="size-4" />
                    Agregar sección
                </button>

                <div x-show="open" x-transition x-cloak class="absolute inset-x-0 top-full z-10 mt-2 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg">
                    @foreach ($addableSections as $type)
                        <button type="button" @disabled(! $type->isAvailable())
                                class="flex w-full items-start gap-3 px-4 py-3 text-left enabled:hover:bg-zinc-50 disabled:cursor-not-allowed">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-zinc-100 text-zinc-500">
                                <x-icon name="tag" class="size-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2 text-sm font-medium">
                                    {{ $type->label() }}
                                    @unless ($type->isAvailable())
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold tracking-wide text-amber-800 uppercase">Próximamente</span>
                                    @endunless
                                </span>
                                <span class="block text-xs text-zinc-500">{{ $type->description() }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </li>
        </ol>

        {{-- Resumen y generar --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <section class="card">
                <header class="card-header"><h2 class="font-medium">Resumen</h2></header>
                <dl class="grid grid-cols-3 divide-x divide-zinc-100 text-center">
                    <div class="px-2 py-4">
                        <dt class="text-xs text-zinc-500">Categorías</dt>
                        <dd class="mt-1 text-xl font-semibold tabular-nums" x-text="chosen.length"></dd>
                    </div>
                    <div class="px-2 py-4">
                        <dt class="text-xs text-zinc-500">Productos</dt>
                        <dd class="mt-1 text-xl font-semibold tabular-nums" x-text="productCount"></dd>
                    </div>
                    <div class="px-2 py-4">
                        <dt class="text-xs text-zinc-500">Páginas</dt>
                        <dd class="mt-1 text-xl font-semibold tabular-nums" x-text="pageCount"></dd>
                    </div>
                </dl>
                <div class="space-y-3 border-t border-zinc-100 p-5">
                    <button type="submit" class="btn-primary w-full" :disabled="productCount === 0" :class="productCount === 0 && 'opacity-50 cursor-not-allowed'">
                        <x-icon name="download" class="size-4" /> Descargar PDF
                    </button>
                    <button type="submit" name="preview" value="1" formtarget="_blank" class="btn-secondary w-full" :disabled="productCount === 0">
                        <x-icon name="eye" class="size-4" /> Vista previa
                    </button>
                    <p class="text-xs text-zinc-500">Con muchas fotos puede tardar unos segundos.</p>
                </div>
            </section>
        </aside>
    </form>
</x-layouts.app>
