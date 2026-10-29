{{-- Formulario compartido por create y edit. Variables: $supplier --}}
<section class="card">
    <div class="grid gap-5 p-5 sm:grid-cols-2">
        <x-form.field label="Nombre o razón social" for="name" required class="sm:col-span-2">
            <input type="text" id="name" name="name" value="{{ old('name', $supplier->name) }}" class="form-input" required autofocus>
        </x-form.field>

        <x-form.field label="CUIT" for="cuit" hint="Con o sin guiones.">
            <input type="text" id="cuit" name="cuit" value="{{ old('cuit', $supplier->cuit) }}" class="form-input font-mono" placeholder="30-12345678-9" inputmode="numeric">
        </x-form.field>

        <x-form.field label="Persona de contacto" for="contact_name">
            <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" class="form-input">
        </x-form.field>

        <x-form.field label="Teléfono / WhatsApp" for="phone">
            <input type="tel" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-input">
        </x-form.field>

        <x-form.field label="Email" for="email">
            <input type="email" id="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-input">
        </x-form.field>

        <x-form.field label="Dirección" for="address" class="sm:col-span-2">
            <input type="text" id="address" name="address" value="{{ old('address', $supplier->address) }}" class="form-input">
        </x-form.field>

        <x-form.field label="Notas" for="notes" hint="Condiciones de pago, días de entrega, etc." class="sm:col-span-2">
            <textarea id="notes" name="notes" rows="3" class="form-input">{{ old('notes', $supplier->notes) }}</textarea>
        </x-form.field>
    </div>
</section>
