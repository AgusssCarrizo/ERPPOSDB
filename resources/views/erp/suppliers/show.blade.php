<x-layouts.app :title="$supplier->name">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.suppliers.index') }}" class="hover:text-zinc-900">Proveedores</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">{{ $supplier->name }}</span>
    </x-slot:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('erp.suppliers.index') }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-semibold tracking-tight">{{ $supplier->name }}</h1>
                <p class="text-sm text-zinc-500"><span class="font-mono">{{ $supplier->cuit ?? 'Sin CUIT' }}</span></p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('erp.suppliers.edit', $supplier) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Editar</a>
            <a href="{{ route('erp.suppliers.payments.create', $supplier) }}" class="btn-secondary"><x-icon name="banknotes" class="size-4" /> Registrar pago</a>
            <a href="{{ route('erp.purchases.create', ['supplier' => $supplier->id]) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nueva compra</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            {{-- Saldo --}}
            <section class="card p-5">
                <h2 class="text-sm font-medium text-zinc-500">Saldo de la cuenta corriente</h2>
                <div class="mt-2 tabular-nums"><x-supplier-balance :balance="$balance" size="lg" /></div>
            </section>

            {{-- Datos de contacto --}}
            <section class="card">
                <header class="card-header"><h2 class="font-medium">Contacto</h2></header>
                <dl class="divide-y divide-zinc-100 text-sm">
                    @foreach (['contact_name' => 'Persona', 'phone' => 'Teléfono', 'email' => 'Email', 'address' => 'Dirección'] as $field => $label)
                        <div class="flex justify-between gap-4 px-5 py-3">
                            <dt class="text-zinc-500">{{ $label }}</dt>
                            <dd class="truncate text-right">{{ $supplier->$field ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($supplier->notes)
                    <p class="border-t border-zinc-100 px-5 py-3 text-sm whitespace-pre-line text-zinc-600">{{ $supplier->notes }}</p>
                @endif
            </section>

            @if ($entries->isEmpty())
                <form method="POST" action="{{ route('erp.suppliers.destroy', $supplier) }}" onsubmit="return confirm('¿Eliminar este proveedor?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-700">Eliminar proveedor</button>
                </form>
            @endif
        </div>

        {{-- Cuenta corriente --}}
        <section class="card overflow-hidden lg:col-span-2">
            <header class="card-header">
                <h2 class="font-medium">Cuenta corriente</h2>
                <p class="text-sm text-zinc-500">Las compras suman deuda, los pagos la descuentan.</p>
            </header>

            @if ($entries->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-zinc-500">Todavía no hay compras ni pagos con este proveedor.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50 text-left text-xs font-medium tracking-wide text-zinc-500 uppercase">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Detalle</th>
                                <th class="px-4 py-3 text-right">Compra</th>
                                <th class="px-4 py-3 text-right">Pago</th>
                                <th class="px-4 py-3 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($entries as $entry)
                                <tr class="hover:bg-zinc-50/70">
                                    <td class="px-4 py-3 whitespace-nowrap text-zinc-600 tabular-nums">{{ $entry['date']->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        @if ($entry['url'])
                                            <a href="{{ $entry['url'] }}" class="text-zinc-900 hover:text-indigo-600">{{ $entry['description'] }}</a>
                                        @else
                                            <span class="text-zinc-700">{{ $entry['description'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-rose-600 tabular-nums">{{ $entry['debit'] ? money($entry['debit']) : '' }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap text-emerald-600 tabular-nums">{{ $entry['credit'] ? money($entry['credit']) : '' }}</td>
                                    <td class="px-4 py-3 text-right font-medium whitespace-nowrap tabular-nums">{{ money($entry['balance']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
