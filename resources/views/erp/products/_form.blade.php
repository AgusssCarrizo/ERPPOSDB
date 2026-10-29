{{--
    Formulario compartido por create y edit.
    Variables: $product, $categories, $brands, $units, $taxRates
    El cálculo costo/margen/precio y la vista previa de imagen viven en resources/js/app.js (productForm).
--}}
<div class="grid gap-6 lg:grid-cols-3"
     x-data="productForm({
        cost: @js((float) old('cost_price', $product->cost_price ?? 0)),
        price: @js((float) old('sale_price', $product->sale_price ?? 0)),
        tax: @js((float) old('tax_rate', $product->tax_rate ?? 21)),
        taxPresets: @js(array_map('floatval', array_keys($taxRates))),
        image: @js($product->image_url),
     })">

    {{-- Columna principal --}}
    <div class="space-y-6 lg:col-span-2">

        {{-- Información general --}}
        <section class="card">
            <header class="card-header">
                <h2 class="font-medium">Información general</h2>
                <p class="text-sm text-zinc-500">Cómo se ve el producto en el sistema y en la tienda.</p>
            </header>
            <div class="grid gap-5 p-5 sm:grid-cols-6">
                <x-form.field label="Nombre" for="name" required class="sm:col-span-6">
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" class="form-input" required autofocus>
                </x-form.field>

                <x-form.field label="Descripción" for="description" hint="Se muestra en la ficha del producto de la tienda online." class="sm:col-span-6">
                    <textarea id="description" name="description" rows="4" class="form-input">{{ old('description', $product->description) }}</textarea>
                </x-form.field>

                <x-form.field label="Categoría" for="category_id" class="sm:col-span-2">
                    <select id="category_id" name="category_id" class="form-input">
                        <option value="">Sin categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>

                @if ($product->exists)
                    {{-- Después de creado, el stock solo cambia con movimientos de inventario --}}
                    <div class="sm:col-span-2">
                        <span class="block text-sm font-medium text-zinc-700">Stock actual</span>
                        <div class="mt-1.5 flex items-center justify-between gap-2 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2">
                            <span class="text-sm font-medium tabular-nums">{{ $product->stock }}</span>
                            <a href="{{ route('erp.inventory.create', ['product' => $product->id]) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Ajustar →</a>
                        </div>
                        <p class="mt-1.5 text-xs text-zinc-500">Se modifica desde Inventario.</p>
                    </div>
                @else
                    <x-form.field label="Stock inicial" for="stock" required hint="Queda registrado como primer movimiento." class="sm:col-span-2">
                        <input type="number" step="1" min="0" id="stock" name="stock" value="{{ old('stock', 0) }}" class="form-input tabular-nums" required>
                    </x-form.field>
                @endif

                <x-form.field label="Stock mínimo" for="min_stock" required hint="Debajo de esto: stock bajo." class="sm:col-span-2">
                    <input type="number" step="1" min="0" id="min_stock" name="min_stock" value="{{ old('min_stock', $product->min_stock ?? 0) }}" class="form-input tabular-nums" required>
                </x-form.field>

                <x-form.field label="Marca" for="brand_id" class="sm:col-span-3">
                    <select id="brand_id" name="brand_id" class="form-input">
                        <option value="">Sin marca</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>

                <x-form.field label="Unidad de medida" for="unit" required class="sm:col-span-3">
                    <select id="unit" name="unit" class="form-input">
                        @foreach ($units as $value => $label)
                            <option value="{{ $value }}" @selected(old('unit', $product->unit) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-form.field>
            </div>
        </section>

        {{-- Precios: costo -> margen -> precio -> IVA --}}
        <section class="card">
            <header class="card-header">
                <h2 class="font-medium">Precios</h2>
                <p class="text-sm text-zinc-500">Cargá el margen y se calcula el precio, o cargá el precio y se calcula el margen.</p>
            </header>
            <div class="grid gap-5 p-5 sm:grid-cols-2 xl:grid-cols-4">
                <x-form.field label="Costo" for="cost_price" required>
                    <div class="relative">
                        <span class="input-prefix">$</span>
                        <input type="number" step="0.01" min="0" id="cost_price" name="cost_price"
                               x-model.number="cost" @input="onCostInput"
                               class="form-input pl-7 tabular-nums" required>
                    </div>
                </x-form.field>

                <x-form.field label="Margen sobre costo" for="margin">
                    <div class="relative">
                        <input type="number" step="0.01" id="margin"
                               x-model.number="margin" @input="onMarginInput" :disabled="!(cost > 0)"
                               :placeholder="cost > 0 ? '' : 'Cargá el costo'"
                               class="form-input pr-8 tabular-nums disabled:bg-zinc-50 disabled:text-zinc-400"
                               :class="margin !== null && margin < 15 && 'text-rose-600'">
                        <span class="input-suffix">%</span>
                    </div>
                </x-form.field>

                <x-form.field label="Precio de venta (sin IVA)" for="sale_price" required>
                    <div class="relative">
                        <span class="input-prefix">$</span>
                        <input type="number" step="0.01" min="0" id="sale_price" name="sale_price"
                               x-model.number="price" @input="onPriceInput"
                               class="form-input pl-7 tabular-nums" required>
                    </div>
                </x-form.field>

                <x-form.field label="IVA" for="tax_rate" required>
                    <select x-model="taxOption" @change="onTaxOptionChange" class="form-input" aria-label="Alícuota de IVA">
                        @foreach ($taxRates as $rate => $label)
                            <option value="{{ $rate }}">{{ $label }}</option>
                        @endforeach
                        <option value="custom">Otro (manual)…</option>
                    </select>

                    {{-- Este input es el que se envía; solo se ve cuando se elige "Otro" --}}
                    <div class="relative mt-2" x-show="taxOption === 'custom'" x-cloak>
                        <input type="number" step="0.01" min="0" max="100" id="tax_rate" name="tax_rate"
                               x-model.number="tax" x-ref="customTax" placeholder="Ej: 5"
                               class="form-input pr-8 tabular-nums" required>
                        <span class="input-suffix">%</span>
                    </div>
                </x-form.field>
            </div>

            {{-- Resumen calculado en vivo --}}
            <dl class="grid grid-cols-3 divide-x divide-zinc-200 border-t border-zinc-200 bg-zinc-50/60 text-center">
                <div class="px-3 py-4">
                    <dt class="text-xs text-zinc-500">Ganancia por unidad</dt>
                    <dd class="mt-1 font-semibold tabular-nums" :class="profit < 0 ? 'text-rose-600' : 'text-zinc-900'" x-text="money(profit)"></dd>
                </div>
                <div class="px-3 py-4">
                    <dt class="text-xs text-zinc-500">IVA (<span x-text="(tax || 0).toLocaleString('es-AR')"></span>%)</dt>
                    <dd class="mt-1 font-semibold tabular-nums text-zinc-900" x-text="money(taxAmount)"></dd>
                </div>
                <div class="px-3 py-4">
                    <dt class="text-xs text-zinc-500">Precio final (con IVA)</dt>
                    <dd class="mt-1 font-semibold tabular-nums text-indigo-600" x-text="money(finalPrice)"></dd>
                </div>
            </dl>
        </section>

        {{-- Identificación --}}
        <section class="card">
            <header class="card-header">
                <h2 class="font-medium">Identificación</h2>
                <p class="text-sm text-zinc-500">El código de barras es el que lee el escáner del punto de venta.</p>
            </header>
            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <x-form.field label="SKU (código interno)" for="sku" required>
                    <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" class="form-input font-mono" placeholder="BZ-00001" required>
                </x-form.field>

                <x-form.field label="Código de barras (EAN)" for="barcode">
                    <div class="relative">
                        <x-icon name="barcode" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
                        <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="form-input pl-9 font-mono" inputmode="numeric">
                    </div>
                </x-form.field>
            </div>
        </section>
    </div>

    {{-- Columna lateral --}}
    <div class="space-y-6">

        {{-- Imagen --}}
        <section class="card">
            <header class="card-header">
                <h2 class="font-medium">Imagen</h2>
            </header>
            <div class="p-5">
                <label for="image" class="group relative flex aspect-square cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 text-center transition hover:border-indigo-400">
                    <template x-if="preview">
                        <img :src="preview" alt="Vista previa" class="absolute inset-0 size-full object-cover">
                    </template>
                    <template x-if="!preview">
                        <span class="flex flex-col items-center px-4 text-zinc-500">
                            <x-icon name="photo" class="size-10 text-zinc-400" />
                            <span class="mt-2 text-sm font-medium text-indigo-600">Subir imagen</span>
                            <span class="text-xs">PNG o JPG, hasta 2 MB</span>
                        </span>
                    </template>
                </label>
                <input type="file" id="image" name="image" accept="image/*" class="sr-only" x-ref="image" @change="pickImage">
                <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">

                <button type="button" x-show="preview" x-cloak @click="clearImage" class="mt-3 text-sm font-medium text-rose-600 hover:text-rose-700">
                    Quitar imagen
                </button>

                @error('image')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- Canales --}}
        <section class="card">
            <header class="card-header">
                <h2 class="font-medium">Canales de venta</h2>
            </header>
            <div class="divide-y divide-zinc-100">
                <label class="flex cursor-pointer items-start justify-between gap-4 p-5">
                    <span>
                        <span class="block text-sm font-medium">Activo / Punto de venta</span>
                        <span class="block text-xs text-zinc-500">Se puede vender en el POS y aparece en el ERP.</span>
                    </span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="toggle" @checked(old('is_active', $product->is_active))>
                </label>

                <label class="flex cursor-pointer items-start justify-between gap-4 p-5">
                    <span>
                        <span class="block text-sm font-medium">Publicado en tienda online</span>
                        <span class="block text-xs text-zinc-500">Visible para clientes en el e-commerce.</span>
                    </span>
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" class="toggle" @checked(old('is_published', $product->is_published))>
                </label>

                <label class="flex cursor-pointer items-start justify-between gap-4 p-5">
                    <span>
                        <span class="flex items-center gap-1.5 text-sm font-medium">
                            <x-icon name="heart" class="size-4 fill-rose-500 text-rose-500" /> Destacado
                        </span>
                        <span class="block text-xs text-zinc-500">Aparece en "Lo más buscado" de la portada de la tienda.</span>
                    </span>
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" class="toggle" @checked(old('is_featured', $product->is_featured))>
                </label>

                <div class="p-5">
                    <x-form.field label="Precio anterior (tachado)" for="compare_price" hint="Final, con IVA. Si es mayor al precio final, la tienda lo muestra tachado con el cartel OFERTA.">
                        <div class="relative">
                            <span class="input-prefix">$</span>
                            <input type="number" step="0.01" min="0" id="compare_price" name="compare_price"
                                   value="{{ old('compare_price', $product->compare_price) }}" placeholder="Sin oferta"
                                   class="form-input pl-7 tabular-nums">
                        </div>
                    </x-form.field>
                </div>

                <div class="p-5">
                    <x-form.field label="URL en la tienda" for="slug" hint="Dejalo vacío para generarlo a partir del nombre.">
                        <div class="flex rounded-md shadow-xs">
                            <span class="inline-flex items-center rounded-l-md border border-r-0 border-zinc-300 bg-zinc-50 px-2.5 text-xs text-zinc-500">/tienda/producto/</span>
                            <input type="text" id="slug" name="slug" value="{{ old('slug', $product->slug) }}" class="form-input rounded-l-none font-mono text-xs">
                        </div>
                    </x-form.field>
                </div>
            </div>
        </section>
    </div>
</div>
