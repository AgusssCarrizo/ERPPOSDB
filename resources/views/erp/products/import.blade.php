<x-layouts.app title="Importar productos">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.products.index') }}" class="hover:text-zinc-900">Productos</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">Importar</span>
    </x-slot:breadcrumbs>

    @php
        // Columnas que entiende el importador (ver App\Services\ProductImport::COLUMNS).
        $columns = [
            ['nombre', 'Sí', 'Nombre del producto. No puede repetirse.'],
            ['categoria', 'No', 'Si no existe, se crea (después cargale la foto de portada).'],
            ['sku', 'No', 'Código interno. Vacío = se genera solo (DB-00001, DB-00002…).'],
            ['costo', 'Sí', 'Lo que te cuesta, por unidad.'],
            ['precio', 'Uno de los tres', 'Precio final con IVA: lo que paga el cliente.'],
            ['precio_venta', 'Uno de los tres', 'Precio sin IVA (como lo guarda el sistema).'],
            ['margen', 'Uno de los tres', 'Margen sobre el costo en %: costo 1000 y margen 50 = 1500 sin IVA.'],
            ['iva', 'No', 'En %. Vacío = 21.'],
            ['stock', 'No', 'Lo que hay hoy. Queda como "Stock inicial" en Inventario. Vacío = 0.'],
            ['stock_minimo', 'No', 'Debajo de esto avisa stock bajo. Vacío = 0.'],
            ['unidad', 'No', implode(', ', array_keys(\App\Models\Product::UNITS)).'. Vacío = unidad.'],
            ['descripcion', 'No', 'Texto libre.'],
            ['activo', 'No', 'si / no. Vacío = si (se puede vender).'],
            ['tienda', 'No', 'si / no: publicado en la tienda online. Vacío = no.'],
            ['codigo_de_barras', 'No', 'Si no lo tenés, no pongas la columna o dejala vacía.'],
        ];
    @endphp

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('erp.products.index') }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Importar productos</h1>
            <p class="text-sm text-zinc-500">Cargá muchos productos de una vez desde una planilla de Excel.</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Subir --}}
        <section class="card self-start">
            <header class="card-header">
                <h2 class="font-medium">1. Subí la planilla</h2>
                <p class="text-sm text-zinc-500">Antes de guardar vas a ver cómo queda cada producto.</p>
            </header>
            <form method="POST" action="{{ route('erp.products.import.preview') }}" enctype="multipart/form-data" class="space-y-4 p-5">
                @csrf
                <x-form.field label="Archivo .xlsx o .csv" for="file" required>
                    <input type="file" id="file" name="file" accept=".xlsx,.csv,.txt" required
                           class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200">
                </x-form.field>
                <button type="submit" class="btn-primary w-full"><x-icon name="upload" class="size-4" /> Revisar planilla</button>
            </form>
            <div class="border-t border-zinc-200 p-5 text-sm text-zinc-600">
                <p>¿No sabés cómo armarla? Bajá la plantilla, completala en Excel y guardala.</p>
                <a href="{{ route('erp.products.import.template') }}" class="btn-secondary mt-3 w-full"><x-icon name="download" class="size-4" /> Descargar plantilla</a>
            </div>
        </section>

        {{-- Columnas --}}
        <section class="card overflow-hidden lg:col-span-2">
            <header class="card-header">
                <h2 class="font-medium">Columnas de la planilla</h2>
                <p class="text-sm text-zinc-500">La primera fila tiene que tener estos nombres (mayúsculas y tildes no importan). El orden da igual y las columnas que no uses se pueden borrar.</p>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Columna</th>
                            <th class="px-4 py-3">Obligatoria</th>
                            <th class="px-4 py-3">Qué va</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($columns as [$column, $required, $help])
                            <tr>
                                <td class="px-4 py-2.5 font-mono text-xs whitespace-nowrap">{{ $column }}</td>
                                <td @class(['px-4 py-2.5 whitespace-nowrap', 'font-medium text-zinc-900' => $required !== 'No', 'text-zinc-400' => $required === 'No'])>{{ $required }}</td>
                                <td class="px-4 py-2.5 text-zinc-600">{{ $help }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="border-t border-zinc-100 px-5 py-3 text-xs text-zinc-500">
                Los importes pueden ir como 1500, 1500,50, 1.500 o $ 1.500. Solo se crean productos nuevos: si un nombre o SKU ya existe, esa fila se marca como error.
            </p>
        </section>
    </div>
</x-layouts.app>
