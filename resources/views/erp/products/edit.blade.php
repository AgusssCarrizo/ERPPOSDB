<x-layouts.app :title="'Editar '.$product->name">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.products.index') }}" class="hover:text-zinc-900">Productos</a> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.products.show', $product) }}" class="hover:text-zinc-900">{{ $product->name }}</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">Editar</span>
    </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('erp.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('erp.products.show', $product) }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-semibold tracking-tight">Editar producto</h1>
                    <p class="truncate text-sm text-zinc-500">{{ $product->name }} · <span class="font-mono">{{ $product->sku }}</span></p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('erp.products.show', $product) }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">Guardar cambios</button>
            </div>
        </div>

        @include('erp.products._form')
    </form>
</x-layouts.app>
