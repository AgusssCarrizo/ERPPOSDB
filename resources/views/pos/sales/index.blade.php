<x-layouts.app title="Ventas">
    <x-slot:breadcrumbs>
        <span>Punto de venta</span> <span class="mx-1.5 text-zinc-300">/</span> <span class="text-zinc-900">Ventas</span>
    </x-slot:breadcrumbs>

    @php
        $isToday = ($filters['from'] ?? null) === today()->toDateString() && ($filters['to'] ?? null) === today()->toDateString() && empty($filters['method']);
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Ventas</h1>
            <p class="mt-1 text-sm text-zinc-500">Lo vendido en el mostrador y cuánto entró por cada medio de pago.</p>
        </div>
        <a href="{{ route('pos.index') }}" class="btn-primary"><x-icon name="calculator" class="size-4" /> Vender</a>
    </div>

    {{-- Resumen del período --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="sm:col-span-2">
            <x-stat-card :label="($isToday ? 'Vendido hoy' : 'Vendido en el período').' · '.$stats['count'].' '.($stats['count'] === 1 ? 'venta' : 'ventas')" :value="money($stats['total'])" icon="receipt" tone="indigo" />
        </div>
        @foreach ($methods as $method)
            <x-stat-card :label="$method->label()" :value="money($byMethod[$method->value] ?? 0)" :icon="$method->icon()" />
        @endforeach
    </div>

    <form method="GET" class="mt-6 flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-3">
        <label class="flex items-center gap-2 text-sm text-zinc-500">
            Desde <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-input w-auto" onchange="this.form.submit()">
        </label>
        <label class="flex items-center gap-2 text-sm text-zinc-500">
            Hasta <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-input w-auto" onchange="this.form.submit()">
        </label>
        <select name="method" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Todos los medios de pago</option>
            @foreach ($methods as $method)
                <option value="{{ $method->value }}" @selected(($filters['method'] ?? '') === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
        @unless ($isToday)
            <a href="{{ route('pos.sales.index') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-900">Ver hoy</a>
        @endunless
        <a href="{{ route('pos.sales.index', ['from' => '', 'to' => '']) }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-900">Ver todas</a>
    </form>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white">
        @if ($sales->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-400"><x-icon name="receipt" class="size-6" /></span>
                <h3 class="mt-4 font-medium">{{ $isToday ? 'Todavía no hay ventas hoy' : 'No hay ventas para mostrar' }}</h3>
                <a href="{{ route('pos.index') }}" class="btn-primary mt-5"><x-icon name="calculator" class="size-4" /> Ir a vender</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Venta</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Pago</th>
                            <th class="px-4 py-3 text-right">Unidades</th>
                            <th class="px-4 py-3 text-right">Descuento</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($sales as $sale)
                            <tr class="hover:bg-zinc-50/70">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('pos.sales.show', $sale) }}" class="font-mono font-medium text-zinc-900 hover:text-indigo-600">{{ $sale->number }}</a>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-zinc-600 tabular-nums">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($sale->payments as $payment)
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $payment->method->badgeClasses() }}"
                                                  @if ($sale->payments->count() > 1) title="{{ money($payment->amount) }}" @endif>
                                                {{ $payment->method->shortLabel() }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ (int) $sale->units }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap text-zinc-500 tabular-nums">{{ $sale->discount > 0 ? '− '.money($sale->discount) : '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium whitespace-nowrap tabular-nums">{{ money($sale->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($sales->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3">{{ $sales->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.app>
