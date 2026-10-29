{{-- Envuelve un input con su label, error de validación y texto de ayuda. --}}
@props(['label', 'for', 'hint' => null, 'required' => false])

<div {{ $attributes }}>
    <label for="{{ $for }}" class="block text-sm font-medium text-zinc-700">
        {{ $label }}
        @if ($required)
            <span class="text-rose-500">*</span>
        @endif
    </label>

    <div class="mt-1.5">
        {{ $slot }}
    </div>

    @if ($errors->has($for))
        <p class="mt-1.5 text-xs text-rose-600">{{ $errors->first($for) }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-zinc-500">{{ $hint }}</p>
    @endif
</div>
