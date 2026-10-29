<x-layouts.app title="Tienda online">
    <x-slot:breadcrumbs>
        <span>E-commerce</span> <span class="mx-1.5 text-zinc-300">/</span> <span class="text-zinc-900">Tienda online</span>
    </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('erp.store.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Tienda online</h1>
                <p class="mt-1 text-sm text-zinc-500">Textos e imágenes de la portada. Los productos salen del ERP: publicados, activos y con stock.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('shop.home') }}" target="_blank" class="btn-secondary">
                    <x-icon name="eye" class="size-4" /> Ver tienda
                </a>
                <button type="submit" class="btn-primary">Guardar cambios</button>
            </div>
        </div>

        {{-- Qué productos se ven --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <x-stat-card label="Productos a la venta en la tienda" :value="$publishedCount" icon="globe" tone="emerald"
                         :href="route('erp.products.index', ['channel' => 'web'])" />
            <x-stat-card label="Destacados ❤ en “Lo más buscado”" :value="$featuredCount" icon="heart" tone="rose"
                         :href="route('erp.products.index', ['channel' => 'featured'])" />
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Portada --}}
                <section class="card">
                    <header class="card-header">
                        <h2 class="font-medium">Portada</h2>
                        <p class="text-sm text-zinc-500">El título va en itálica y la palabra destacada debajo, en negrita: “<i>Momentos que</i> <b>inspiran.</b>”</p>
                    </header>
                    <div class="grid gap-5 p-5 sm:grid-cols-2">
                        <x-form.field label="Barra de anuncios" for="announcement" hint="La franja negra de arriba de todo. Vacía = no se muestra." class="sm:col-span-2">
                            <input type="text" id="announcement" name="announcement" value="{{ old('announcement', $settings->announcement) }}" class="form-input">
                        </x-form.field>

                        <x-form.field label="Título" for="hero_title" required>
                            <input type="text" id="hero_title" name="hero_title" value="{{ old('hero_title', $settings->hero_title) }}" class="form-input" required>
                        </x-form.field>

                        <x-form.field label="Palabra destacada" for="hero_highlight">
                            <input type="text" id="hero_highlight" name="hero_highlight" value="{{ old('hero_highlight', $settings->hero_highlight) }}" class="form-input">
                        </x-form.field>

                        <x-form.field label="Texto" for="hero_text" class="sm:col-span-2">
                            <textarea id="hero_text" name="hero_text" rows="2" class="form-input">{{ old('hero_text', $settings->hero_text) }}</textarea>
                        </x-form.field>

                        <x-form.field label="Texto del botón" for="hero_button_label" required hint="Lleva a todos los productos.">
                            <input type="text" id="hero_button_label" name="hero_button_label" value="{{ old('hero_button_label', $settings->hero_button_label) }}" class="form-input" required>
                        </x-form.field>
                    </div>
                </section>

                {{-- Promoción --}}
                <section class="card">
                    <header class="card-header">
                        <h2 class="font-medium">Promoción especial</h2>
                        <p class="text-sm text-zinc-500">
                            Un producto (o un combo cargado como producto) con su foto y precio. Para que diga “Precio especial”,
                            cargale un <b>precio anterior</b> en el producto.
                        </p>
                    </header>
                    <div class="grid gap-5 p-5 sm:grid-cols-2">
                        <x-form.field label="Producto" for="promo_product_id" hint="Si no tiene stock, el bloque no se muestra." class="sm:col-span-2">
                            <select id="promo_product_id" name="promo_product_id" class="form-input">
                                <option value="">— Sin promoción —</option>
                                @foreach ($promoProducts as $product)
                                    <option value="{{ $product->id }}" @selected(old('promo_product_id', $settings->promo_product_id) == $product->id)>
                                        {{ $product->name }}{{ $product->stock > 0 ? '' : ' (sin stock)' }}
                                    </option>
                                @endforeach
                            </select>
                        </x-form.field>

                        <x-form.field label="Antetítulo" for="promo_eyebrow">
                            <input type="text" id="promo_eyebrow" name="promo_eyebrow" value="{{ old('promo_eyebrow', $settings->promo_eyebrow) }}" class="form-input">
                        </x-form.field>

                        <x-form.field label="Texto del botón" for="promo_button_label" required>
                            <input type="text" id="promo_button_label" name="promo_button_label" value="{{ old('promo_button_label', $settings->promo_button_label) }}" class="form-input" required>
                        </x-form.field>

                        <x-form.field label="Título" for="promo_title" hint="Vacío = el nombre del producto.">
                            <input type="text" id="promo_title" name="promo_title" value="{{ old('promo_title', $settings->promo_title) }}" class="form-input">
                        </x-form.field>

                        <x-form.field label="Palabra destacada" for="promo_highlight">
                            <input type="text" id="promo_highlight" name="promo_highlight" value="{{ old('promo_highlight', $settings->promo_highlight) }}" class="form-input">
                        </x-form.field>

                        <x-form.field label="Texto" for="promo_text" class="sm:col-span-2">
                            <textarea id="promo_text" name="promo_text" rows="2" class="form-input">{{ old('promo_text', $settings->promo_text) }}</textarea>
                        </x-form.field>

                        <x-form.field label="Etiqueta sobre la foto" for="promo_badge_title" hint="Ej: Súper vendido. Vacía = sin etiqueta.">
                            <input type="text" id="promo_badge_title" name="promo_badge_title" value="{{ old('promo_badge_title', $settings->promo_badge_title) }}" class="form-input">
                        </x-form.field>

                        <x-form.field label="Texto de la etiqueta" for="promo_badge_text">
                            <input type="text" id="promo_badge_text" name="promo_badge_text" value="{{ old('promo_badge_text', $settings->promo_badge_text) }}" class="form-input">
                        </x-form.field>
                    </div>
                </section>

                {{-- Beneficios --}}
                <section class="card">
                    <header class="card-header">
                        <h2 class="font-medium">Beneficios</h2>
                        <p class="text-sm text-zinc-500">Los tres recuadros al final de la portada y en cada producto.</p>
                    </header>
                    <div class="divide-y divide-zinc-100">
                        @foreach (old('benefits', $settings->benefits) as $index => $benefit)
                            <div class="grid gap-4 p-5 sm:grid-cols-[auto_1fr_2fr]">
                                <span class="grid size-10 place-items-center rounded-lg bg-zinc-100 text-zinc-500">
                                    <x-icon :name="\App\Models\StoreSetting::BENEFIT_ICONS[$index] ?? 'check'" />
                                </span>
                                <x-form.field label="Título" :for="'benefits.'.$index.'.title'">
                                    <input type="text" id="benefits.{{ $index }}.title" name="benefits[{{ $index }}][title]" value="{{ $benefit['title'] ?? '' }}" class="form-input" required>
                                </x-form.field>
                                <x-form.field label="Texto" :for="'benefits.'.$index.'.text'">
                                    <input type="text" id="benefits.{{ $index }}.text" name="benefits[{{ $index }}][text]" value="{{ $benefit['text'] ?? '' }}" class="form-input" required>
                                </x-form.field>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                {{-- Foto de portada --}}
                <section class="card" x-data="imagePicker(@js($settings->hero_image_url))">
                    <header class="card-header">
                        <h2 class="font-medium">Foto de portada</h2>
                        <p class="text-sm text-zinc-500">Horizontal, con espacio libre a la izquierda para el texto. Si no subís ninguna, se usa la de la cocina.</p>
                    </header>
                    <div class="p-5" x-data="{ removeImage: false }">
                        <label for="hero_image" @class([
                            'relative flex aspect-video cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed bg-zinc-50 text-center transition hover:border-indigo-400',
                            'border-rose-400' => $errors->has('hero_image'),
                            'border-zinc-300' => ! $errors->has('hero_image'),
                        ])>
                            <template x-if="preview && !removeImage">
                                <img :src="preview" alt="Vista previa" class="absolute inset-0 size-full object-cover">
                            </template>
                            <template x-if="!preview || removeImage">
                                <span class="flex flex-col items-center px-4 text-zinc-500">
                                    <x-icon name="photo" class="size-10 text-zinc-400" />
                                    <span class="mt-2 text-sm font-medium text-indigo-600">Subir foto</span>
                                    <span class="text-xs">PNG o JPG, hasta 4 MB</span>
                                </span>
                            </template>
                        </label>
                        <input type="file" id="hero_image" name="hero_image" accept="image/*" class="sr-only" @change="pick($event); removeImage = false">

                        @if ($settings->hero_image_url)
                            <label class="mt-3 flex items-center gap-2 text-xs text-zinc-600">
                                <input type="hidden" name="remove_hero_image" value="0">
                                <input type="checkbox" name="remove_hero_image" value="1" x-model="removeImage" class="rounded border-zinc-300">
                                Quitar la foto (vuelve la foto de portada original)
                            </label>
                        @endif

                        @error('hero_image')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                {{-- Contacto y pie --}}
                <section class="card">
                    <header class="card-header">
                        <h2 class="font-medium">Contacto y pie de página</h2>
                    </header>
                    <div class="space-y-5 p-5">
                        <x-form.field label="WhatsApp" for="whatsapp" hint="Con código de país y 9: +54 9 3388 458046. Los botones de compra abren este chat.">
                            <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $settings->whatsapp) }}" class="form-input">
                        </x-form.field>

                        <x-form.field label="Instagram" for="instagram">
                            <div class="relative">
                                <span class="input-prefix">@</span>
                                <input type="text" id="instagram" name="instagram" value="{{ old('instagram', ltrim((string) $settings->instagram, '@')) }}" class="form-input pl-7">
                            </div>
                        </x-form.field>

                        <x-form.field label="Ubicación" for="address">
                            <input type="text" id="address" name="address" value="{{ old('address', $settings->address) }}" class="form-input">
                        </x-form.field>

                        <x-form.field label="Descripción" for="about_text" hint="Debajo del logo, en el pie.">
                            <textarea id="about_text" name="about_text" rows="3" class="form-input">{{ old('about_text', $settings->about_text) }}</textarea>
                        </x-form.field>
                    </div>
                </section>
            </div>
        </div>
    </form>
</x-layouts.app>
