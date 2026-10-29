<x-layouts.app title="Nueva categoría">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.categories.index') }}" class="hover:text-zinc-900">Categorías</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">Nueva</span>
    </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('erp.categories.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('erp.categories.index') }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
                <h1 class="text-2xl font-semibold tracking-tight">Nueva categoría</h1>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('erp.categories.index') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">Guardar categoría</button>
            </div>
        </div>

        @include('erp.categories._form')
    </form>
</x-layouts.app>
