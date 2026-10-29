@props(['product'])

@php
    $styles = [
        'ok' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'low' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'out' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
    ];
    $labels = ['ok' => 'En stock', 'low' => 'Stock bajo', 'out' => 'Sin stock'];
    $status = $product->stock_status;
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset', $styles[$status]]) }}>
    <span class="tabular-nums">{{ $product->stock }}</span>
    <span class="opacity-75">· {{ $labels[$status] }}</span>
</span>
