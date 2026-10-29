{{--
    Tabla de movimientos de stock. Se usa en Inventario y en el detalle del producto.
    Variables: $movements, $showProduct (bool, default true)
--}}
@php $showProduct ??= true; @endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-zinc-200 text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
            <tr>
                <th class="px-4 py-3">Fecha</th>
                @if ($showProduct)
                    <th class="px-4 py-3">Producto</th>
                @endif
                <th class="px-4 py-3">Movimiento</th>
                <th class="px-4 py-3 text-right">Cantidad</th>
                <th class="px-4 py-3 text-right">Stock</th>
                <th class="px-4 py-3">Nota</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
            @foreach ($movements as $movement)
                <tr class="hover:bg-zinc-50/70">
                    <td class="px-4 py-3 whitespace-nowrap text-zinc-600 tabular-nums" title="{{ $movement->created_at->format('d/m/Y H:i:s') }}">
                        {{ $movement->created_at->format('d/m/Y H:i') }}
                    </td>
                    @if ($showProduct)
                        <td class="px-4 py-3">
                            <a href="{{ route('erp.products.show', $movement->product) }}" class="font-medium text-zinc-900 hover:text-indigo-600">{{ $movement->product->name }}</a>
                            <span class="block font-mono text-xs text-zinc-500">{{ $movement->product->sku }}</span>
                        </td>
                    @endif
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $movement->type->badgeClasses() }}">
                            {{ $movement->type->label() }}
                        </span>
                        @if ($movement->reason)
                            <span class="ml-1 text-xs text-zinc-500">{{ $movement->reason->label() }}</span>
                        @endif
                    </td>
                    <td @class([
                        'px-4 py-3 text-right font-semibold tabular-nums whitespace-nowrap',
                        'text-emerald-600' => $movement->quantity > 0,
                        'text-rose-600' => $movement->quantity < 0,
                    ])>
                        {{ $movement->quantity > 0 ? '+' : '−' }}{{ abs($movement->quantity) }}
                    </td>
                    <td class="px-4 py-3 text-right text-zinc-700 tabular-nums">{{ $movement->stock_after }}</td>
                    <td class="max-w-xs truncate px-4 py-3 text-zinc-500" title="{{ $movement->note }}">{{ $movement->note ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
