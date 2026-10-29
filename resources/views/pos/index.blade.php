<x-layouts.app title="Vender">
    <x-slot:breadcrumbs>
        <span>Punto de venta</span> <span class="mx-1.5 text-zinc-300">/</span> <span class="text-zinc-900">Vender</span>
    </x-slot:breadcrumbs>

    <div x-data="posTerminal({
            products: @js($products),
            methods: @js(collect($methods)->map(fn ($method) => ['value' => $method->value, 'label' => $method->shortLabel(), 'icon' => $method->icon(), 'givesChange' => $method->givesChange()])),
            storeUrl: @js(route('pos.sales.store')),
         })"
         @keydown.window.f2.prevent="$refs.search.focus()"
         @keydown.window.f9.prevent="charge()"
         class="grid gap-6 lg:grid-cols-5">

        {{-- Productos --}}
        <section class="lg:col-span-3">
            <div class="relative">
                <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-zinc-400"><x-icon name="search" /></span>
                <input type="search" x-ref="search" x-model="query" @keydown.enter.prevent="searchEnter" autofocus autocomplete="off"
                       class="form-input py-3 pl-11 text-base" placeholder="Buscar por nombre, SKU o escanear código…  (F2)">
            </div>

            @if ($categories->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" @click="categoryId = null"
                            class="rounded-full px-3 py-1 text-sm font-medium transition"
                            :class="categoryId === null ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-600 ring-1 ring-zinc-200 hover:bg-zinc-100'">Todas</button>
                    @foreach ($categories as $category)
                        <button type="button" @click="categoryId = {{ $category->id }}"
                                class="rounded-full px-3 py-1 text-sm font-medium transition"
                                :class="categoryId === {{ $category->id }} ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-600 ring-1 ring-zinc-200 hover:bg-zinc-100'">{{ $category->name }}</button>
                    @endforeach
                </div>
            @endif

            <div x-show="notice" x-transition x-cloak class="mt-3 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                <x-icon name="warning" class="size-4 text-amber-600" /> <span x-text="notice"></span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                <template x-for="product in results" :key="product.id">
                    <button type="button" @click="add(product)" :disabled="product.stock <= 0"
                            class="group relative flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white text-left transition hover:border-indigo-300 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:border-zinc-200 disabled:hover:shadow-none">
                        <div class="grid aspect-[4/3] place-items-center bg-zinc-100 text-zinc-300">
                            <template x-if="product.image"><img :src="product.image" alt="" loading="lazy" class="size-full object-cover"></template>
                            <template x-if="!product.image"><x-icon name="photo" class="size-8" /></template>
                        </div>
                        <div class="flex flex-1 flex-col p-3">
                            <span class="line-clamp-2 text-sm font-medium" x-text="product.name"></span>
                            <span class="mt-auto pt-2 text-base font-semibold tabular-nums" x-text="money(product.price)"></span>
                            <span class="text-xs" :class="product.stock <= 0 ? 'text-rose-600' : 'text-zinc-500'"
                                  x-text="product.stock <= 0 ? 'Sin stock' : `Stock: ${product.stock}`"></span>
                        </div>
                        <span x-show="inCart(product)" x-text="inCart(product)" x-cloak
                              class="absolute top-2 right-2 grid size-7 place-items-center rounded-full bg-indigo-600 text-sm font-semibold text-white shadow"></span>
                    </button>
                </template>
            </div>

            <div x-show="!results.length" x-cloak class="mt-4 rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center text-sm text-zinc-500">
                @if ($products->isEmpty())
                    No hay productos activos para vender.
                    <a href="{{ route('erp.products.create') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Cargá uno</a>.
                @else
                    No hay productos que coincidan con la búsqueda.
                @endif
            </div>
        </section>

        {{-- Venta actual --}}
        <aside class="lg:col-span-2 lg:sticky lg:top-24 lg:self-start">
            <section class="card overflow-hidden">
                <header class="card-header flex items-center justify-between">
                    <h2 class="font-medium">Venta <span class="text-sm font-normal text-zinc-500" x-show="units" x-text="`· ${units} ${units === 1 ? 'unidad' : 'unidades'}`"></span></h2>
                    <button type="button" x-show="cart.length" x-cloak @click="clearCart" class="text-sm text-zinc-500 hover:text-rose-600">Vaciar</button>
                </header>

                {{-- Líneas --}}
                <div class="max-h-[40vh] divide-y divide-zinc-100 overflow-y-auto">
                    <template x-for="(line, index) in cart" :key="line.product_id">
                        <div class="px-4 py-3">
                            <div class="flex items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium" x-text="line.name"></p>
                                    <p class="text-xs text-zinc-500 tabular-nums" x-text="money(line.price)"></p>
                                </div>
                                <div class="flex items-center rounded-md ring-1 ring-zinc-300">
                                    <button type="button" @click="decrement(line)" class="grid size-8 place-items-center text-zinc-500 hover:text-zinc-900" title="Uno menos"><x-icon name="minus" class="size-4" /></button>
                                    <input type="number" min="1" :max="line.stock" x-model.number="line.quantity" @change="fixQuantity(line)"
                                           class="w-12 border-0 bg-transparent p-0 text-center text-sm tabular-nums focus:ring-0 focus:outline-none" aria-label="Cantidad">
                                    <button type="button" @click="increment(line)" class="grid size-8 place-items-center text-zinc-500 hover:text-zinc-900" title="Uno más"><x-icon name="plus" class="size-4" /></button>
                                </div>
                                <p class="w-24 pt-1 text-right text-sm font-medium tabular-nums" x-text="money(line.price * line.quantity)"></p>
                                <button type="button" @click="remove(index)" class="icon-btn size-7 hover:bg-rose-50 hover:text-rose-600" title="Quitar"><x-icon name="x" class="size-4" /></button>
                            </div>
                            <p x-show="lineError(index)" x-text="lineError(index)" x-cloak class="mt-1 text-xs text-rose-600"></p>
                        </div>
                    </template>

                    <div x-show="!cart.length" class="px-6 py-10 text-center text-sm text-zinc-500">
                        <span class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-zinc-100 text-zinc-400"><x-icon name="cart" class="size-6" /></span>
                        Tocá un producto o escaneá su código para empezar.
                    </div>
                </div>

                {{-- Totales --}}
                <div class="space-y-3 border-t border-zinc-200 p-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500">Subtotal</span>
                        <span class="tabular-nums" x-text="money(subtotal)"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-zinc-500">Descuento</span>
                        <div class="flex items-center gap-2">
                            <div class="flex rounded-md text-xs ring-1 ring-zinc-300">
                                <button type="button" @click="discountMode = '$'" class="rounded-l-md px-2 py-1" :class="discountMode === '$' ? 'bg-zinc-900 text-white' : 'text-zinc-500'">$</button>
                                <button type="button" @click="discountMode = '%'" class="rounded-r-md px-2 py-1" :class="discountMode === '%' ? 'bg-zinc-900 text-white' : 'text-zinc-500'">%</button>
                            </div>
                            <input type="number" min="0" step="0.01" x-model="discountValue" class="form-input w-24 py-1 text-right tabular-nums" placeholder="0" aria-label="Descuento">
                        </div>
                    </div>
                    <p x-show="discount > 0" x-cloak class="-mt-1 text-right text-xs text-emerald-700 tabular-nums" x-text="`− ${money(discount)}`"></p>
                    <div class="flex items-end justify-between border-t border-zinc-200 pt-3">
                        <span class="font-medium">Total</span>
                        <span class="text-3xl font-semibold tracking-tight tabular-nums" x-text="money(total)"></span>
                    </div>
                </div>

                {{-- Pago --}}
                <div class="space-y-4 border-t border-zinc-200 bg-zinc-50/60 p-4">
                    <template x-if="!split">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($methods as $method)
                                    <button type="button" @click="pick('{{ $method->value }}')"
                                            class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 text-sm font-medium transition"
                                            :class="method === '{{ $method->value }}' ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50'">
                                        <x-icon :name="$method->icon()" class="size-5" /> {{ $method->shortLabel() }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Efectivo: con cuánto paga y vuelto --}}
                            <div x-show="isCash" x-cloak class="space-y-2">
                                <label for="tendered" class="block text-sm font-medium text-zinc-700">Paga con</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <span class="input-prefix">$</span>
                                        <input type="number" id="tendered" min="0" step="0.01" x-model="tendered" class="form-input pl-7 tabular-nums" placeholder="Justo">
                                    </div>
                                    <template x-for="amount in cashSuggestions" :key="amount">
                                        <button type="button" @click="tendered = amount" class="btn-secondary px-2.5 tabular-nums" x-text="amount.toLocaleString('es-AR')"></button>
                                    </template>
                                </div>
                                <div x-show="change !== null" class="flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 text-emerald-800">
                                    <span class="text-sm font-medium">Vuelto</span>
                                    <span class="text-xl font-semibold tabular-nums" x-text="money(change)"></span>
                                </div>
                                <p x-show="missingCash !== null" class="text-sm text-rose-600" x-text="`Faltan ${money(missingCash)}`"></p>
                            </div>

                            <button type="button" @click="startSplit" x-show="cart.length" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                Dividir el pago (ej.: parte efectivo, parte tarjeta)
                            </button>
                        </div>
                    </template>

                    {{-- Pago dividido --}}
                    <template x-if="split">
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-zinc-700">Pago dividido</p>
                            <template x-for="(payment, index) in splitPayments" :key="payment.key">
                                <div class="flex gap-2">
                                    <select x-model="payment.method" class="form-input flex-1" aria-label="Medio de pago">
                                        <template x-for="option in methods" :key="option.value">
                                            <option :value="option.value" x-text="option.label" :selected="option.value === payment.method"></option>
                                        </template>
                                    </select>
                                    <div class="relative w-32">
                                        <span class="input-prefix">$</span>
                                        <input type="number" min="0" step="0.01" x-model="payment.amount" class="form-input pl-7 tabular-nums" aria-label="Importe">
                                    </div>
                                    <button type="button" @click="removeSplitPayment(index)" class="icon-btn mt-1 hover:bg-rose-50 hover:text-rose-600" title="Quitar"><x-icon name="x" class="size-4" /></button>
                                </div>
                            </template>
                            <div class="flex items-center justify-between pt-1 text-sm">
                                <button type="button" @click="addSplitPayment" x-show="splitPayments.length < 4" class="inline-flex items-center gap-1 font-medium text-indigo-600 hover:text-indigo-500">
                                    <x-icon name="plus" class="size-4" /> Otro medio
                                </button>
                                <span class="tabular-nums" :class="Math.abs(remaining) < 0.01 ? 'text-emerald-700' : 'font-medium text-rose-600'"
                                      x-text="Math.abs(remaining) < 0.01 ? 'Cubre el total ✓' : (remaining > 0 ? `Faltan ${money(remaining)}` : `Sobran ${money(-remaining)}`)"></span>
                            </div>
                            <button type="button" @click="stopSplit" class="text-sm text-zinc-500 hover:text-zinc-900">Pagar con un solo medio</button>
                        </div>
                    </template>

                    <input type="text" x-model="notes" maxlength="255" class="form-input text-sm" placeholder="Nota (opcional)" aria-label="Nota">

                    <template x-if="generalErrors.length">
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                            <template x-for="message in generalErrors" :key="message"><p x-text="message"></p></template>
                        </div>
                    </template>

                    <button type="button" @click="charge" :disabled="blocker !== null || saving"
                            class="btn-primary w-full py-3 text-base disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-show="!saving" x-text="blocker ?? `Cobrar ${money(total)}`"></span>
                        <span x-show="saving" x-cloak>Registrando…</span>
                    </button>
                    <p class="text-center text-xs text-zinc-400">F2 buscar · F9 cobrar</p>
                </div>
            </section>
        </aside>

        {{-- Venta registrada --}}
        <div x-show="lastSale" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-zinc-900/50 p-4" @keydown.escape.window="lastSale && newSale()">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl" @click.outside="newSale">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="check" class="size-6" /></span>
                <h2 class="mt-4 text-lg font-semibold">Venta <span class="font-mono" x-text="lastSale?.number"></span> cobrada</h2>
                <p class="mt-1 text-sm text-zinc-500">Total <span class="font-medium text-zinc-900 tabular-nums" x-text="money(lastSale?.total)"></span></p>

                <div x-show="lastSale?.change > 0" class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-emerald-800">
                    <p class="text-sm font-medium">Vuelto</p>
                    <p class="text-3xl font-semibold tabular-nums" x-text="money(lastSale?.change)"></p>
                </div>

                <div class="mt-6 grid gap-2">
                    <button type="button" x-ref="newSale" @click="newSale" class="btn-primary py-2.5">Nueva venta</button>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="printTicket" class="btn-secondary"><x-icon name="printer" class="size-4" /> Ticket</button>
                        <a :href="lastSale?.url" class="btn-secondary">Ver venta</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
