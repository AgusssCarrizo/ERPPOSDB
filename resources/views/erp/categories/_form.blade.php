{{--
    Formulario compartido por create y edit. Variables: $category
--}}
<div class="grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2">
        <header class="card-header">
            <h2 class="font-medium">Datos de la categoría</h2>
        </header>
        <div class="grid gap-5 p-5 sm:grid-cols-2">
            <x-form.field label="Nombre" for="name" required class="sm:col-span-2">
                <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" class="form-input" required autofocus>
            </x-form.field>

            <x-form.field label="Orden en el catálogo" for="position" required hint="Las categorías se muestran de menor a mayor.">
                <input type="number" min="0" step="1" id="position" name="position" value="{{ old('position', $category->position) }}" class="form-input tabular-nums" required>
            </x-form.field>

            <x-form.field label="URL en la tienda" for="slug" hint="Dejalo vacío para generarlo a partir del nombre.">
                <div class="flex rounded-md shadow-xs">
                    <span class="inline-flex items-center rounded-l-md border border-r-0 border-zinc-300 bg-zinc-50 px-2.5 text-xs text-zinc-500">/tienda/categoria/</span>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $category->slug) }}" class="form-input rounded-l-none font-mono text-xs">
                </div>
            </x-form.field>

            <label class="flex cursor-pointer items-start justify-between gap-4 rounded-lg border border-zinc-200 p-4 sm:col-span-2">
                <span>
                    <span class="block text-sm font-medium">Activa</span>
                    <span class="block text-xs text-zinc-500">Las inactivas no aparecen en el catálogo ni en la tienda.</span>
                </span>
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="toggle" @checked(old('is_active', $category->is_active))>
            </label>
        </div>
    </section>

    {{-- Foto de portada --}}
    <section class="card" x-data="imagePicker(@js($category->image_url))">
        <header class="card-header">
            <h2 class="font-medium">Foto de portada <span class="text-rose-500">*</span></h2>
            <p class="text-sm text-zinc-500">Identifica a la categoría en el catálogo.</p>
        </header>
        <div class="p-5">
            <label for="image" @class([
                'relative flex aspect-4/3 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed bg-zinc-50 text-center transition hover:border-indigo-400',
                'border-rose-400' => $errors->has('image'),
                'border-zinc-300' => ! $errors->has('image'),
            ])>
                <template x-if="preview">
                    <img :src="preview" alt="Vista previa" class="absolute inset-0 size-full object-cover">
                </template>
                <template x-if="!preview">
                    <span class="flex flex-col items-center px-4 text-zinc-500">
                        <x-icon name="photo" class="size-10 text-zinc-400" />
                        <span class="mt-2 text-sm font-medium text-indigo-600">Subir foto</span>
                        <span class="text-xs">PNG o JPG, hasta 4 MB</span>
                    </span>
                </template>
            </label>
            <input type="file" id="image" name="image" accept="image/*" class="sr-only" @change="pick">

            <p x-show="preview" x-cloak class="mt-3 text-xs text-zinc-500">Hacé click en la foto para cambiarla.</p>

            @error('image')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </section>
</div>
