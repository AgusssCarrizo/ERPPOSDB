<x-layouts.app title="Registrar pago">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.suppliers.index') }}" class="hover:text-zinc-900">Proveedores</a> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.suppliers.show', $supplier) }}" class="hover:text-zinc-900">{{ $supplier->name }}</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">Registrar pago</span>
    </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('erp.suppliers.payments.store', $supplier) }}" class="mx-auto max-w-xl"
          x-data="{ amount: @js(old('amount', '')) }">
        @csrf

        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('erp.suppliers.show', $supplier) }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Registrar pago</h1>
                <p class="text-sm text-zinc-500">{{ $supplier->name }}</p>
            </div>
        </div>

        <section class="card">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                <span class="text-sm text-zinc-500">Saldo actual</span>
                <span class="text-right tabular-nums"><x-supplier-balance :balance="$balance" /></span>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <x-form.field label="Importe" for="amount" required class="sm:col-span-2">
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="input-prefix">$</span>
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount" x-model="amount" class="form-input pl-7 tabular-nums" required autofocus>
                        </div>
                        @if ($balance > 0)
                            <button type="button" class="btn-secondary whitespace-nowrap" @click="amount = @js($balance)">Pagar todo</button>
                        @endif
                    </div>
                </x-form.field>

                <x-form.field label="Medio de pago" for="method" required>
                    <select id="method" name="method" class="form-input" required>
                        @foreach ($methods as $method)
                            <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </x-form.field>

                <x-form.field label="Fecha" for="date" required>
                    <input type="date" id="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-input" required>
                </x-form.field>

                <x-form.field label="Nota" for="note" hint="Ej: número de transferencia o de cheque." class="sm:col-span-2">
                    <input type="text" id="note" name="note" value="{{ old('note') }}" maxlength="255" class="form-input">
                </x-form.field>
            </div>
        </section>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('erp.suppliers.show', $supplier) }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Registrar pago</button>
        </div>
    </form>
</x-layouts.app>
