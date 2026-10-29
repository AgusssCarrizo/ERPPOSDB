<x-layouts.app title="Ajustar stock">
    <x-slot:breadcrumbs>
        <span>ERP</span> <span class="mx-1.5 text-zinc-300">/</span>
        <a href="{{ route('erp.inventory.index') }}" class="hover:text-zinc-900">Inventario</a> <span class="mx-1.5 text-zinc-300">/</span>
        <span class="text-zinc-900">Ajustar stock</span>
    </x-slot:breadcrumbs>

    @php
        $back = $return === 'inventory' || ! $selected
            ? route('erp.inventory.index')
            : route('erp.products.show', $selected);
    @endphp

    <form method="POST" action="{{ route('erp.inventory.store') }}" class="mx-auto max-w-2xl"
          x-data="stockAdjust({
              products: @js($products->mapWithKeys(fn ($p) => [$p->id => ['stock' => $p->stock]])),
              reasons: @js(collect($reasons)->map(fn ($r) => ['value' => $r->value, 'label' => $r->label(), 'direction' => $r->direction()])),
              productId: @js((string) old('product_id', $selected)),
              mode: @js(old('mode', 'remove')),
              quantity: @js(old('quantity', '')),
              reason: @js(old('reason', '')),
          })">
        @csrf
        <input type="hidden" name="return" value="{{ $return }}">

        <div class="mb-6 flex items-center gap-3">
            <a href="{{ $back }}" class="icon-btn" title="Volver"><x-icon name="arrow-left" /></a>
            <h1 class="text-2xl font-semibold tracking-tight">Ajustar stock</h1>
        </div>

        <section class="card">
            <div class="space-y-6 p-5">
                <x-form.field label="Producto" for="product_id" required>
                    <select id="product_id" name="product_id" x-model="productId" class="form-input" required>
                        <option value="">Elegí un producto…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>
                        @endforeach
                    </select>
                </x-form.field>

                {{-- Modo: sumar / restar / conteo --}}
                <div>
                    <span class="block text-sm font-medium text-zinc-700">¿Qué querés hacer?</span>
                    <div class="mt-1.5 grid grid-cols-3 gap-2">
                        @foreach ($modes as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="mode" value="{{ $value }}" x-model="mode" class="peer sr-only">
                                <span class="flex flex-col items-center rounded-lg border border-zinc-300 px-3 py-3 text-center transition peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:ring-1 peer-checked:ring-indigo-600 hover:bg-zinc-50">
                                    <span class="text-sm font-medium">{{ $label }}</span>
                                    <span class="text-xs text-zinc-500">
                                        {{ ['add' => 'Entra mercadería', 'remove' => 'Sale mercadería', 'count' => 'Cargo lo que conté'][$value] }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.field for="quantity" required label="Cantidad">
                        <input type="number" id="quantity" name="quantity" x-model.number="quantity" step="1" :min="mode === 'count' ? 0 : 1"
                               class="form-input tabular-nums" :placeholder="mode === 'count' ? 'Unidades contadas' : 'Unidades'" required>
                    </x-form.field>

                    <x-form.field for="reason" label="Motivo" x-show="mode !== 'count'">
                        <select id="reason" name="reason" x-model="reason" class="form-input" :required="mode !== 'count'" :disabled="mode === 'count'">
                            <option value="">Elegí un motivo…</option>
                            <template x-for="option in availableReasons" :key="option.value">
                                <option :value="option.value" x-text="option.label" :selected="option.value === reason"></option>
                            </template>
                        </select>
                    </x-form.field>
                </div>

                <x-form.field label="Nota" for="note" hint="Opcional. Ej: se cayó de la góndola, conteo de fin de mes.">
                    <input type="text" id="note" name="note" value="{{ old('note') }}" maxlength="255" class="form-input">
                </x-form.field>
            </div>

            {{-- Vista previa del resultado --}}
            <dl class="grid grid-cols-3 divide-x divide-zinc-200 border-t border-zinc-200 bg-zinc-50/60 text-center" x-show="productId" x-cloak>
                <div class="px-3 py-4">
                    <dt class="text-xs text-zinc-500">Stock actual</dt>
                    <dd class="mt-1 text-lg font-semibold tabular-nums" x-text="current"></dd>
                </div>
                <div class="px-3 py-4">
                    <dt class="text-xs text-zinc-500">Movimiento</dt>
                    <dd class="mt-1 text-lg font-semibold tabular-nums"
                        :class="difference > 0 ? 'text-emerald-600' : (difference < 0 ? 'text-rose-600' : 'text-zinc-400')"
                        x-text="difference === null ? '—' : (difference > 0 ? '+' : (difference < 0 ? '−' : '')) + Math.abs(difference)"></dd>
                </div>
                <div class="px-3 py-4">
                    <dt class="text-xs text-zinc-500">Stock después</dt>
                    <dd class="mt-1 text-lg font-semibold tabular-nums" :class="after < 0 ? 'text-rose-600' : 'text-indigo-600'"
                        x-text="after === null ? '—' : after"></dd>
                </div>
            </dl>
            <p x-show="after < 0" x-cloak class="border-t border-rose-100 bg-rose-50 px-5 py-2 text-sm text-rose-700">
                No podés sacar más de lo que hay en stock.
            </p>
        </section>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ $back }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary" :disabled="after < 0" :class="after < 0 && 'opacity-50 cursor-not-allowed'">Guardar ajuste</button>
        </div>
    </form>
</x-layouts.app>
