<x-layouts.app :title="'Editar '.$supplier->name">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.suppliers.index') }}" class="hover:text-zinc-900">Proveedores</a> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.suppliers.show', $supplier) }}" class="hover:text-zinc-900">{{ $supplier->name }}</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">Editar</span>
    </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('erp.suppliers.update', $supplier) }}" class="mx-auto max-w-3xl">
        @csrf
        @method('PUT')

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('erp.suppliers.show', $supplier) }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
                <h1 class="text-2xl font-semibold tracking-tight">Editar proveedor</h1>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('erp.suppliers.show', $supplier) }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary">Guardar cambios</button>
            </div>
        </div>

        @include('erp.suppliers._form')
    </form>
</x-layouts.app>
