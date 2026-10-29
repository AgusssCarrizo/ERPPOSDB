<x-layouts.app :title="'Editar '.$category->name">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.categories.index') }}" class="hover:text-zinc-900">Categorías</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">{{ $category->name }}</span>
    </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('erp.categories.update', $category) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('erp.categories.index') }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-semibold tracking-tight">Editar categoría</h1>
                    <p class="truncate text-sm text-zinc-500">
                        {{ $category->name }} ·
                        <a href="{{ route('erp.products.index', ['category' => $category->id]) }}" class="hover:text-indigo-600">
                            {{ $category->products_count }} {{ $category->products_count === 1 ? 'producto' : 'productos' }}
                        </a>
                    </p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('erp.categories.index') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">Guardar cambios</button>
            </div>
        </div>

        @include('erp.categories._form')
    </form>
</x-layouts.app>
