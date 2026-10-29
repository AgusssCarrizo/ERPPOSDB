<x-layouts.app title="Nueva compra">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.purchases.index') }}" class="hover:text-zinc-900">Compras</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">Nueva</span>
    </x-slot:breadcrumbs>

    @if ($suppliers->isEmpty())
        <div class="card mx-auto max-w-xl px-6 py-12 text-center">
            <h1 class="text-lg font-semibold">Primero cargá un proveedor</h1>
            <p class="mt-1 text-sm text-zinc-500">Cada compra se asigna a un proveedor para llevar su cuenta corriente.</p>
            <a href="{{ route('erp.suppliers.create') }}" class="btn-primary mt-5"><x-icon name="plus" class="size-4" /> Nuevo proveedor</a>
        </div>
    @else
        <form method="POST" action="{{ route('erp.purchases.store') }}"
              x-data="purchaseForm({
                  products: @js($products->mapWithKeys(fn ($p) => [$p->id => ['cost' => (float) $p->cost_price, 'stock' => $p->stock]])),
                  items: @js(array_values(old('items', [['product_id' => '', 'quantity' => 1, 'unit_cost' => '']]))),
                  taxAmount: @js(old('tax_amount', '')),
                  paidNow: @js(old('paid_now', '')),
                  errors: @js($errors->getMessages()),
              })">
            @csrf

            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('erp.purchases.index') }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
                    <h1 class="text-2xl font-semibold tracking-tight">Nueva compra</h1>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('erp.purchases.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">Registrar compra</button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    {{-- Datos de la compra --}}
                    <section class="card">
                        <div class="grid gap-5 p-5 sm:grid-cols-3">
                            <x-form.field label="Proveedor" for="supplier_id" required>
                                <select id="supplier_id" name="supplier_id" class="form-input" required>
                                    <option value="">Elegí…</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedSupplier) == $supplier->id)>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </x-form.field>
                            <x-form.field label="Fecha de recepción" for="date" required>
                                <input type="date" id="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-input" required>
                            </x-form.field>
                            <x-form.field label="N° de factura / remito" for="invoice_number">
                                <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number') }}" class="form-input font-mono" placeholder="0001-00001234">
                            </x-form.field>
                        </div>
                    </section>

                    {{-- Productos --}}
                    <section class="card">
                        <header class="card-header">
                            <h2 class="font-medium">Productos recibidos</h2>
                            <p class="text-sm text-zinc-500">El costo se completa con el último registrado; cambialo si vino distinto.</p>
                        </header>

                        @error('items')
                            <p class="border-b border-rose-100 bg-rose-50 px-5 py-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror

                        <div class="divide-y divide-zinc-100">
                            <template x-for="(item, index) in items" :key="item.key">
                                <div class="grid grid-cols-12 items-start gap-3 px-5 py-4">
                                    <div class="col-span-12 sm:col-span-6">
                                        <label class="text-xs font-medium text-zinc-500 sm:sr-only" :for="`item-${index}`">Producto</label>
                                        <select :id="`item-${index}`" :name="`items[${index}][product_id]`" x-model="item.product_id" @change="pickProduct(item)" class="form-input" required>
                                            <option value="">Elegí un producto…</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-zinc-500" x-show="item.product_id" x-cloak>
                                            Stock actual: <span x-text="products[item.product_id]?.stock"></span>
                                            · Último costo: <span x-text="money(products[item.product_id]?.cost)"></span>
                                            <template x-if="costChanged(item)"><span class="font-medium text-amber-700"> · cambia el costo</span></template>
                                        </p>
                                        <p class="mt-1 text-xs text-rose-600" x-show="errors[`items.${index}.product_id`]" x-text="errors[`items.${index}.product_id`]?.[0]" x-cloak></p>
                                    </div>
                                    <div class="col-span-4 sm:col-span-2">
                                        <label class="text-xs font-medium text-zinc-500 sm:sr-only">Cantidad</label>
                                        <input type="number" min="1" step="1" :name="`items[${index}][quantity]`" x-model.number="item.quantity" class="form-input tabular-nums" placeholder="Cant." required>
                                    </div>
                                    <div class="col-span-5 sm:col-span-3">
                                        <label class="text-xs font-medium text-zinc-500 sm:sr-only">Costo unitario</label>
                                        <div class="relative">
                                            <span class="input-prefix">$</span>
                                            <input type="number" min="0" step="0.01" :name="`items[${index}][unit_cost]`" x-model.number="item.unit_cost" class="form-input pl-7 tabular-nums" placeholder="Costo" required>
                                        </div>
                                        <p class="mt-1 text-right text-xs text-zinc-500 tabular-nums" x-text="money(subtotal(item))"></p>
                                    </div>
                                    <div class="col-span-3 flex justify-end sm:col-span-1 sm:pt-1">
                                        <button type="button" class="icon-btn hover:bg-rose-50 hover:text-rose-600" title="Quitar" @click="removeItem(index)" x-show="items.length > 1">
                                            <x-icon name="trash" class="size-4" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="border-t border-zinc-100 px-5 py-3">
                            <button type="button" @click="addItem" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                <x-icon name="plus" class="size-4" /> Agregar producto
                            </button>
                        </div>
                    </section>

                    <x-form.field label="Notas" for="notes">
                        <textarea id="notes" name="notes" rows="2" class="form-input">{{ old('notes') }}</textarea>
                    </x-form.field>
                </div>

                {{-- Totales y pago --}}
                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <section class="card">
                        <header class="card-header"><h2 class="font-medium">Total</h2></header>
                        <div class="space-y-4 p-5">
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Subtotal productos</span>
                                <span class="font-medium tabular-nums" x-text="money(itemsTotal)"></span>
                            </div>
                            <x-form.field label="IVA / otros cargos de la factura" for="tax_amount">
                                <div class="relative">
                                    <span class="input-prefix">$</span>
                                    <input type="number" min="0" step="0.01" id="tax_amount" name="tax_amount" x-model.number="taxAmount" class="form-input pl-7 tabular-nums" placeholder="0">
                                </div>
                            </x-form.field>
                            <div class="flex justify-between border-t border-zinc-200 pt-4">
                                <span class="font-medium">Total de la compra</span>
                                <span class="text-lg font-semibold tabular-nums" x-text="money(total)"></span>
                            </div>
                        </div>

                        <div class="space-y-4 border-t border-zinc-200 bg-zinc-50/60 p-5">
                            <x-form.field label="¿Pagaste algo ahora?" for="paid_now" hint="Lo que no pagues queda como deuda con el proveedor.">
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <span class="input-prefix">$</span>
                                        <input type="number" min="0" step="0.01" id="paid_now" name="paid_now" x-model.number="paidNow" class="form-input pl-7 tabular-nums" placeholder="0">
                                    </div>
                                    <button type="button" class="btn-secondary whitespace-nowrap" @click="paidNow = total">Todo</button>
                                </div>
                            </x-form.field>
                            <x-form.field label="Medio de pago" for="payment_method" x-show="paidNow > 0">
                                <select id="payment_method" name="payment_method" class="form-input" :disabled="!(paidNow > 0)">
                                    @foreach ($methods as $method)
                                        <option value="{{ $method->value }}" @selected(old('payment_method') === $method->value)>{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                            </x-form.field>
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-500">Queda debiendo</span>
                                <span class="font-semibold tabular-nums" :class="debt > 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="money(debt)"></span>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </form>
    @endif
</x-layouts.app>
