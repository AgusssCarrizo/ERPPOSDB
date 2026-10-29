<x-layouts.app title="Proveedores">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span> <span class="text-zinc-900">Proveedores</span>
    </x-slot:breadcrumbs>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Proveedores</h1>
            <p class="mt-1 text-sm text-zinc-500">A quién le comprás y cuánto le debés a cada uno.</p>
        </div>
        <a href="{{ route('erp.suppliers.create') }}" class="btn-primary">
            <x-icon name="plus" class="size-4" /> Nuevo proveedor
        </a>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Proveedores" :value="$suppliers->count()" icon="users" tone="indigo" />
        <x-stat-card label="Deuda total con proveedores" :value="money($totalDebt)" icon="banknotes" :tone="$totalDebt > 0 ? 'rose' : 'emerald'" />
    </div>

    <form method="GET" class="mt-6 flex gap-3 rounded-xl border border-zinc-200 bg-white p-3">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" name="q" value="{{ $search }}" placeholder="Buscar por nombre, CUIT o contacto…" class="form-input pl-9">
        </div>
        <button type="submit" class="btn-secondary">Buscar</button>
    </form>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 bg-white">
        @if ($suppliers->isEmpty())
            <div class="px-6 py-16 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-400"><x-icon name="users" class="size-6" /></span>
                <h3 class="mt-4 font-medium">{{ $search ? 'No hay proveedores que coincidan' : 'Todavía no cargaste proveedores' }}</h3>
                @unless ($search)
                    <a href="{{ route('erp.suppliers.create') }}" class="btn-primary mt-5"><x-icon name="plus" class="size-4" /> Nuevo proveedor</a>
                @endunless
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Proveedor</th>
                            <th class="px-4 py-3">Contacto</th>
                            <th class="px-4 py-3">Última compra</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($suppliers as $supplier)
                            <tr class="hover:bg-zinc-50/70">
                                <td class="px-4 py-3">
                                    <a href="{{ route('erp.suppliers.show', $supplier) }}" class="font-medium text-zinc-900 hover:text-indigo-600">{{ $supplier->name }}</a>
                                    <span class="block font-mono text-xs text-zinc-500">{{ $supplier->cuit ?? 'Sin CUIT' }}</span>
                                </td>
                                <td class="px-4 py-3 text-zinc-600">
                                    {{ $supplier->contact_name ?? '—' }}
                                    <span class="block text-xs text-zinc-500">{{ $supplier->phone }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-zinc-600">
                                    {{ $supplier->last_purchase_date ? \Illuminate\Support\Carbon::parse($supplier->last_purchase_date)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">
                                    <x-supplier-balance :balance="$supplier->balance" />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('erp.purchases.create', ['supplier' => $supplier->id]) }}" class="icon-btn" title="Nueva compra"><x-icon name="truck" class="size-4" /></a>
                                        <a href="{{ route('erp.suppliers.payments.create', $supplier) }}" class="icon-btn" title="Registrar pago"><x-icon name="banknotes" class="size-4" /></a>
                                        <a href="{{ route('erp.suppliers.show', $supplier) }}" class="icon-btn" title="Cuenta corriente"><x-icon name="eye" class="size-4" /></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
