<x-layouts.app title="Revisar importación">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.products.index') }}" class="hover:text-zinc-900">Productos</a> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.products.import') }}" class="hover:text-zinc-900">Importar</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">Revisar</span>
    </x-slot:breadcrumbs>

    @php $total = count($rows); @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('erp.products.import') }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">2. Revisá antes de importar</h1>
                <p class="text-sm text-zinc-500">{{ $total }} {{ $total === 1 ? 'producto' : 'productos' }} en la planilla. Todavía no se guardó nada.</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('erp.products.import') }}" class="btn-secondary">Subir otra</a>
            @if ($errorCount === 0)
                <form method="POST" action="{{ route('erp.products.import.store') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Importar {{ $total }} {{ $total === 1 ? 'producto' : 'productos' }}</button>
                </form>
            @endif
        </div>
    </div>

    @if ($errorCount > 0)
        <div class="mb-4 flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <x-icon name="warning" class="size-5 text-rose-600" />
            <p>{{ $errorCount === 1 ? 'Hay 1 fila con errores' : "Hay {$errorCount} filas con errores" }} (marcadas en rojo). Corregilas en el Excel y volvé a subirlo: no se importa nada hasta que esté todo bien.</p>
        </div>
    @endif

    @if ($newCategories->isNotEmpty())
        <div class="mb-4 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="tag" class="size-5 text-amber-600" />
            <p>Se van a crear {{ $newCategories->count() === 1 ? 'esta categoría' : 'estas categorías' }}: <strong>{{ $newCategories->implode(', ') }}</strong>. Quedan sin foto de portada: cargásela después para el catálogo.</p>
        </div>
    @endif

    @if ($unknownColumns !== [])
        <div class="mb-4 flex items-start gap-3 rounded-lg border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-600">
            <x-icon name="warning" class="size-5 text-zinc-400" />
            <p>Estas columnas no las conozco y se van a ignorar: <span class="font-mono">{{ implode(', ', $unknownColumns) }}</span>. Si alguna es importante, revisá el nombre.</p>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Fila</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3 text-right">Costo</th>
                        <th class="px-4 py-3 text-right">Margen</th>
                        <th class="px-4 py-3 text-right">Precio sin IVA</th>
                        <th class="px-4 py-3 text-right">IVA</th>
                        <th class="px-4 py-3 text-right">Precio final</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($rows as $row)
                        @php $data = $row['data']; $hasErrors = $row['errors'] !== []; @endphp
                        <tr @class(['bg-rose-50/60' => $hasErrors])>
                            <td class="px-4 py-3 align-top text-zinc-400 tabular-nums">{{ $row['line'] }}</td>
                            <td class="px-4 py-3 align-top">
                                <span class="font-medium">{{ $data['name'] ?: '—' }}</span>
                                <span class="block font-mono text-xs text-zinc-500">
                                    {{ $data['sku'] ?? 'SKU automático' }}
                                    @unless ($data['active']) · <span class="text-zinc-400">inactivo</span> @endunless
                                    @if ($data['published']) · <span class="text-indigo-600">tienda</span> @endif
                                </span>
                                @foreach ($row['errors'] as $error)
                                    <span class="mt-1 block text-xs text-rose-700">{{ $error }}</span>
                                @endforeach
                            </td>
                            <td class="px-4 py-3 align-top">
                                {{ $data['category'] ?? '—' }}
                                @if ($data['new_category'])
                                    <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 uppercase">Nueva</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right align-top whitespace-nowrap tabular-nums">{{ money($data['cost']) }}</td>
                            <td class="px-4 py-3 text-right align-top whitespace-nowrap tabular-nums">{{ $data['margin'] === null ? '—' : percent($data['margin']) }}</td>
                            <td class="px-4 py-3 text-right align-top whitespace-nowrap tabular-nums">{{ money($data['net_price']) }}</td>
                            <td class="px-4 py-3 text-right align-top whitespace-nowrap tabular-nums">{{ percent($data['tax']) }}</td>
                            <td class="px-4 py-3 text-right align-top font-medium whitespace-nowrap tabular-nums">{{ money($data['final_price']) }}</td>
                            <td class="px-4 py-3 text-right align-top tabular-nums">{{ $data['stock'] }} <span class="text-xs text-zinc-400">{{ $data['unit'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
