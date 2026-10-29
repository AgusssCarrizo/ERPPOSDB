{{--
    Una categoría: portada con su foto + páginas de productos (3 por página, en zigzag).
    Variables: $category, $cover (data URI o null), $pages (chunks de ['name', 'price', 'image']), $images
--}}
@php
    $name = \App\Catalog\Typography::title($category->name);
    $count = $pages->flatten(1)->count();
@endphp

{{-- Portada de la categoría --}}
<div class="page">
    @if ($cover)
        <img class="abs" src="{{ $cover }}" style="left: 25mm; top: 28mm; width: 160mm; height: 146.6mm;">
    @else
        @include('catalog.partials.placeholder', ['text' => 'SIN FOTO', 'left' => 25, 'top' => 28, 'width' => 160, 'height' => 146.6])
    @endif

    <img class="abs" src="{{ $images->asset('branch-right') }}" style="left: 191mm; top: 150mm; width: 19mm;">

    <div class="abs center" style="top: 186mm; font-size: 32pt; letter-spacing: 6pt;">{{ $name }}</div>
    <div class="rule" style="left: 92mm; top: 206mm; width: 26mm;"></div>
    <div class="abs center muted" style="top: 210mm; font-size: 10pt; letter-spacing: 3pt;">
        <span class="num">{{ $count }}</span> {{ $count === 1 ? 'PRODUCTO' : 'PRODUCTOS' }}
    </div>

    <img class="abs" src="{{ $images->asset('branch-bottom') }}" style="left: 4mm; top: 222mm; width: 62mm;">

    @include('catalog.partials.footer')
</div>

{{-- Páginas de productos --}}
@foreach ($pages as $page)
    <div class="page">
        <div class="abs center" style="top: 13mm; font-size: 12pt; letter-spacing: 4pt;">{{ $name }}</div>
        <div class="rule" style="left: 85mm; top: 23mm; width: 40mm;"></div>

        @foreach ($page->values() as $i => $product)
            @php
                $top = 30 + $i * 77;                       // cada fila mide 77 mm
                $imageLeft = $i % 2 === 0 ? 24 : 120;      // zigzag: foto a la izquierda, a la derecha, a la izquierda
                $textLeft = $i % 2 === 0 ? 96 : 22;
            @endphp

            @if ($product['image'])
                <img class="abs" src="{{ $product['image'] }}" style="left: {{ $imageLeft }}mm; top: {{ $top + 3 }}mm; width: 66mm; height: 66mm;">
            @else
                @include('catalog.partials.placeholder', ['text' => 'SIN IMAGEN', 'left' => $imageLeft, 'top' => $top + 3, 'width' => 66, 'height' => 66])
            @endif

            {{-- Nombre: tabla para centrarlo verticalmente (dompdf no tiene flexbox) --}}
            <table class="abs" style="left: {{ $textLeft }}mm; top: {{ $top + 8 }}mm; width: 92mm; border-collapse: collapse;">
                <tr>
                    <td style="height: 28mm; vertical-align: middle; text-align: center; font-size: 15pt; letter-spacing: 2pt; line-height: 1.3;">
                        {{ \App\Catalog\Typography::title($product['name']) }}
                    </td>
                </tr>
            </table>

            {{-- Precio dentro del marco del diseño (imagen generada con GD: ver PriceTagRenderer) --}}
            <img class="abs" src="{{ $images->priceTag($product['price']) }}" style="left: {{ $textLeft + 14 }}mm; top: {{ $top + 40 }}mm; width: 64mm;">

            @unless ($loop->last)
                <div class="rule" style="left: 60mm; top: {{ $top + 74 }}mm; width: 90mm; border-color: #eee5dc;"></div>
            @endunless
        @endforeach

        @include('catalog.partials.footer')
    </div>
@endforeach
