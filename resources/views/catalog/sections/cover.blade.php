{{-- Portada. Variables: $title, $subtitle, $images --}}
<div class="page">
    <img class="abs" src="{{ $images->asset('branch-left') }}" style="left: 0; top: 78mm; width: 24mm;">
    <img class="abs" src="{{ $images->asset('branch-right') }}" style="left: 191mm; top: 120mm; width: 19mm;">

    <img class="abs" src="{{ $images->asset('logo') }}" style="left: 94mm; top: 58mm; width: 22mm;">

    <div class="abs center" style="top: 98mm; font-size: 44pt; letter-spacing: 8pt;">
        {{ \App\Catalog\Typography::title($title) }}
    </div>

    @if ($subtitle)
        <div class="rule" style="left: 85mm; top: 127mm; width: 40mm;"></div>
        <div class="abs center muted" style="top: 132mm; font-size: 13pt; letter-spacing: 4pt;">
            {{ \App\Catalog\Typography::title($subtitle) }}
        </div>
    @endif

    <img class="abs" src="{{ $images->asset('branch-bottom') }}" style="left: 6mm; top: 200mm; width: 88mm;">

    @include('catalog.partials.footer')
</div>
