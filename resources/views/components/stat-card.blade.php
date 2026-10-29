@props(['label', 'value', 'icon', 'tone' => 'zinc', 'href' => null])

@php
    $tones = [
        'zinc' => 'bg-zinc-100 text-zinc-600',
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 {{ $href ? 'transition hover:border-zinc-300 hover:shadow-sm' : '' }}">
    <span class="grid size-10 shrink-0 place-items-center rounded-lg {{ $tones[$tone] }}">
        <x-icon :name="$icon" />
    </span>
    <div class="min-w-0">
        <p class="truncate text-xs font-medium text-zinc-500">{{ $label }}</p>
        <p class="mt-0.5 truncate text-lg font-semibold tabular-nums text-zinc-900">{{ $value }}</p>
    </div>
</{{ $tag }}>
