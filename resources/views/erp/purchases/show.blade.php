<x-layouts.app :title="'Compra '.$purchase->number">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.purchases.index') }}" class="hover:text-zinc-900">Compras</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">{{ $purchase->number }}</span>
    </x-slot:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('erp.purchases.index') }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Compra <span class="font-mono">{{ $purchase->number }}</span></h1>
                <p class="text-sm text-zinc-500">
                    <a href="{{ route('erp.suppliers.show', $purchase->supplier) }}" class="hover:text-indigo-600">{{ $purchase->supplier->name }}</a>
                    · {{ $purchase->date->format('d/m/Y') }}
                    @if ($purchase->invoice_number) · Fact. <span class="font-mono">{{ $purchase->invoice_number }}</span> @endif
                </p>
            </div>
        </div>
        <a href="{{ route('erp.suppliers.payments.create', $purchase->supplier) }}" class="btn-secondary"><x-icon name="banknotes" class="size-4" /> Registrar pago</a>
    </div>

    {{-- Revisión de precios: productos cuyo costo cambió --}}
    @if ($pendingReviews->isNotEmpty())
        <section class="card mb-6 overflow-hidden border-amber-300">
            <header class="card-header border-amber-200 bg-amber-50">
                <h2 class="flex items-center gap-2 font-medium text-amber-900"><x-icon name="warning" class="size-5 text-amber-600" /> Revisá los precios</h2>
                <p class="text-sm text-amber-800">Estos productos llegaron con otro costo. El precio de venta no se cambió: decidí vos si lo actualizás.</p>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3 text-right">Costo</th>
                            <th class="px-4 py-3 text-right">Precio actual</th>
                            <th class="px-4 py-3 text-right">Margen</th>
                            <th class="px-4 py-3">Precio nuevo (sin IVA)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($pendingReviews as $item)
                            @php
                                $product = $item->product;
                                $oldMargin = $item->previous_cost > 0 ? ($product->sale_price - $item->previous_cost) / $item->previous_cost * 100 : null;
                                $newMargin = $item->unit_cost > 0 ? ($product->sale_price - $item->unit_cost) / $item->unit_cost * 100 : null;
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('erp.products.show', $product) }}" class="font-medium hover:text-indigo-600">{{ $product->name }}</a>
                                    <span class="block font-mono text-xs text-zinc-500">{{ $product->sku }}</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">
                                    <span class="text-zinc-400 line-through">{{ money($item->previous_cost) }}</span>
                                    <span @class(['block font-medium', 'text-rose-600' => $item->unit_cost > $item->previous_cost, 'text-emerald-600' => $item->unit_cost < $item->previous_cost])>
                                        {{ money($item->unit_cost) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">{{ money($product->sale_price) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">
                                    <span class="text-zinc-400">{{ $oldMargin === null ? '—' : percent(round($oldMargin, 1)) }}</span>
                                    <span @class(['block font-medium', 'text-rose-600' => $newMargin !== null && $newMargin < 15])>
                                        {{ $newMargin === null ? '—' : percent(round($newMargin, 1)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('erp.purchases.price-review', $item) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <div class="relative w-32">
                                            <span class="input-prefix">$</span>
                                            <input type="number" step="0.01" min="0" name="sale_price" value="{{ $item->suggested_price }}" class="form-input py-1.5 pl-7 tabular-nums" aria-label="Precio nuevo">
                                        </div>
                                        <button type="submit" name="action" value="apply" class="btn-primary px-3 py-1.5 text-xs">Aplicar</button>
                                        <button type="submit" name="action" value="keep" class="btn-secondary px-3 py-1.5 text-xs" formnovalidate>Dejar igual</button>
                                    </form>
                                    <p class="mt-1 text-xs text-zinc-500">Sugerido: mantiene el margen anterior.</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Detalle --}}
        <section class="card overflow-hidden lg:col-span-2">
            <header class="card-header"><h2 class="font-medium">Productos recibidos</h2></header>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3 text-right">Cantidad</th>
                            <th class="px-4 py-3 text-right">Costo unit.</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($purchase->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('erp.products.show', $item->product) }}" class="hover:text-indigo-600">{{ $item->product->name }}</a>
                                    <span class="block font-mono text-xs text-zinc-500">{{ $item->product->sku }}</span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">+{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">
                                    {{ money($item->unit_cost) }}
                                    @if ($item->cost_changed)
                                        <span class="block text-xs text-zinc-400">antes {{ money($item->previous_cost) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">{{ money($item->subtotal) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($purchase->notes)
                <p class="border-t border-zinc-100 px-5 py-3 text-sm whitespace-pre-line text-zinc-600">{{ $purchase->notes }}</p>
            @endif
        </section>

        {{-- Totales y pagos --}}
        <section class="card self-start">
            <dl class="space-y-3 p-5 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-500">Subtotal</dt><dd class="tabular-nums">{{ money($purchase->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">IVA / otros</dt><dd class="tabular-nums">{{ money($purchase->tax_amount) }}</dd></div>
                <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-semibold"><dt>Total</dt><dd class="tabular-nums">{{ money($purchase->total) }}</dd></div>
            </dl>
            <div class="border-t border-zinc-200 p-5">
                <h3 class="text-sm font-medium">Pagado con la compra</h3>
                @forelse ($purchase->payments as $payment)
                    <p class="mt-2 flex justify-between text-sm">
                        <span class="text-zinc-500">{{ $payment->method->label() }}</span>
                        <span class="text-emerald-600 tabular-nums">{{ money($payment->amount) }}</span>
                    </p>
                @empty
                    <p class="mt-2 text-sm text-zinc-500">Nada: quedó todo en la cuenta corriente.</p>
                @endforelse
                <a href="{{ route('erp.suppliers.show', $purchase->supplier) }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">Ver cuenta corriente →</a>
            </div>
        </section>
    </div>
</x-layouts.app>
