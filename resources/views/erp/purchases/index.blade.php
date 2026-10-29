<x-layouts.app title="Compras">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span> <span class="text-zinc-900">Compras</span>
    </x-slot:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Compras</h1>
            <p class="mt-1 text-sm text-zinc-500">Mercadería recibida de proveedores. Cada compra suma stock y deuda con el proveedor.</p>
        </div>
        <a href="{{ route('erp.purchases.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nueva compra</a>
    </div>

    @if ($pendingReviews > 0)
        <div class="mt-6 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="warning" class="size-5 text-amber-600" />
            {{ $pendingReviews === 1 ? '1 producto cambió de costo y tiene' : "{$pendingReviews} productos cambiaron de costo y tienen" }}
            el precio de venta pendiente de revisión. Entrá a la compra marcada para revisarlo.
        </div>
    @endif

    <form method="GET" class="mt-6 flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-3">
        <select name="supplier" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Todos los proveedores</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(($filters['supplier'] ?? '') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-zinc-500">
            Desde <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-input w-auto" onchange="this.form.submit()">
        </label>
        <label class="flex items-center gap-2 text-sm text-zinc-500">
            Hasta <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-input w-auto" onchange="this.form.submit()">
        </label>
        @if (collect($filters)->filter()->isNotEmpty())
            <a href="{{ route('erp.purchases.index') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-900">Limpiar filtros</a>
        @endif
    </form>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white">
        @if ($purchases->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-400"><x-icon name="truck" class="size-6" /></span>
                <h3 class="mt-4 font-medium">No hay compras para mostrar</h3>
                @if ($suppliers->isEmpty())
                    <p class="mt-1 text-sm text-zinc-500">Primero cargá un proveedor.</p>
                    <a href="{{ route('erp.suppliers.create') }}" class="btn-primary mt-5"><x-icon name="plus" class="size-4" /> Nuevo proveedor</a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Compra</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Proveedor</th>
                            <th class="px-4 py-3">Factura</th>
                            <th class="px-4 py-3 text-right">Productos</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($purchases as $purchase)
                            <tr class="hover:bg-zinc-50/70">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('erp.purchases.show', $purchase) }}" class="font-mono font-medium text-zinc-900 hover:text-indigo-600">{{ $purchase->number }}</a>
                                    @if ($purchase->pending_reviews_count)
                                        <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 uppercase" title="Precios para revisar">Revisar precios</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-zinc-600 tabular-nums">{{ $purchase->date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('erp.suppliers.show', $purchase->supplier) }}" class="hover:text-indigo-600">{{ $purchase->supplier->name }}</a>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-zinc-600">{{ $purchase->invoice_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $purchase->items_count }}</td>
                                <td class="px-4 py-3 text-right font-medium whitespace-nowrap tabular-nums">{{ money($purchase->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($purchases->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3">{{ $purchases->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.app>
