<x-layouts.app :title="'Venta '.$sale->number">
    <x-slot:breadcrumbs>
        <span>Punto de venta</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('pos.sales.index') }}" class="hover:text-zinc-900">Ventas</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">{{ $sale->number }}</span>
    </x-slot:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('pos.sales.index') }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Venta <span class="font-mono">{{ $sale->number }}</span></h1>
                <p class="text-sm text-zinc-500">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        <a href="{{ route('pos.sales.ticket', ['sale' => $sale, 'imprimir' => 1]) }}" target="_blank" class="btn-secondary"><x-icon name="printer" class="size-4" /> Imprimir ticket</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Detalle --}}
        <section class="card overflow-hidden lg:col-span-2">
            <header class="card-header"><h2 class="font-medium">Productos</h2></header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3 text-right">Cantidad</th>
                            <th class="px-4 py-3 text-right">Precio unit.</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($sale->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('erp.products.show', $item->product) }}" class="hover:text-indigo-600">{{ $item->product->name }}</a>
                                    <span class="block font-mono text-xs text-zinc-500">{{ $item->product->sku }}</span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">
                                    {{ money($item->unit_price) }}
                                    <span class="block text-xs text-zinc-400">IVA {{ percent($item->tax_rate) }} incl.</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">{{ money($item->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($sale->notes)
                <p class="border-t border-zinc-100 px-5 py-3 text-sm whitespace-pre-line text-zinc-600">{{ $sale->notes }}</p>
            @endif
        </section>

        {{-- Totales y pagos --}}
        <section class="card self-start">
            <dl class="space-y-3 p-5 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-500">Subtotal</dt><dd class="tabular-nums">{{ money($sale->subtotal) }}</dd></div>
                @if ($sale->discount > 0)
                    <div class="flex justify-between"><dt class="text-zinc-500">Descuento</dt><dd class="text-emerald-700 tabular-nums">− {{ money($sale->discount) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-semibold"><dt>Total</dt><dd class="tabular-nums">{{ money($sale->total) }}</dd></div>
            </dl>
            <div class="border-t border-zinc-200 p-5">
                <h3 class="text-sm font-medium">Cobrado con</h3>
                @foreach ($sale->payments as $payment)
                    <div class="mt-2 text-sm">
                        <p class="flex justify-between">
                            <span class="flex items-center gap-2 text-zinc-600"><x-icon :name="$payment->method->icon()" class="size-4 text-zinc-400" /> {{ $payment->method->label() }}</span>
                            <span class="tabular-nums">{{ money($payment->amount) }}</span>
                        </p>
                        @if ($payment->tendered !== null)
                            <p class="mt-0.5 pl-6 text-xs text-zinc-500 tabular-nums">Pagó con {{ money($payment->tendered) }} · vuelto {{ money($payment->change) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="border-t border-zinc-200 p-5 text-sm">
                <p class="flex justify-between">
                    <span class="text-zinc-500" title="Lo cobrado sin IVA menos el costo de los productos">Ganancia estimada</span>
                    <span @class(['font-medium tabular-nums', 'text-emerald-700' => $sale->profit >= 0, 'text-rose-600' => $sale->profit < 0])>{{ money($sale->profit) }}</span>
                </p>
            </div>
        </section>
    </div>
</x-layouts.app>
