{{-- Saldo con un proveedor: positivo = le debemos, negativo = saldo a favor nuestro. --}}
@props(['balance', 'size' => 'sm'])

@if ($balance > 0)
    <span {{ $attributes->class(['font-semibold text-rose-600', 'text-2xl' => $size === 'lg']) }}>{{ money($balance) }}</span>
    <span class="block text-xs text-zinc-500">le debés</span>
@elseif ($balance < 0)
    <span {{ $attributes->class(['font-semibold text-emerald-600', 'text-2xl' => $size === 'lg']) }}>{{ money(abs($balance)) }}</span>
    <span class="block text-xs text-zinc-500">a tu favor</span>
@else
    <span {{ $attributes->class(['font-medium text-zinc-500', 'text-2xl' => $size === 'lg']) }}>Al día</span>
@endif
