{{--
    Catálogo PDF (A4 vertical). Lo dibuja dompdf, que entiende CSS 2.1:
    sin flexbox ni grid, así que todo se posiciona en milímetros con position: absolute.
    Variables: $sections (list<App\Catalog\Section>), $images (App\Catalog\CatalogImages)
--}}
@php
    $font = fn (string $file) => str_replace('\\', '/', resource_path("fonts/{$file}"));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        @font-face { font-family: 'Playfair'; src: url('{{ $font('PlayfairDisplay-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Bodoni'; src: url('{{ $font('BodoniModa.ttf') }}') format('truetype'); }

        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Playfair'; color: #58351b; }

        .page { position: relative; width: 210mm; height: 296mm; overflow: hidden; page-break-before: always; }
        .page:first-child { page-break-before: avoid; }

        .abs { position: absolute; }
        .center { left: 0; width: 210mm; text-align: center; }
        .num { font-family: 'Bodoni'; }
        .muted { color: #a1846b; }
        .rule { position: absolute; border-top: 0.3mm solid #c9b29c; height: 0; }
        .placeholder { background: #f5f1ec; color: #b5a495; text-align: center; font-size: 9pt; letter-spacing: 2pt; }
    </style>
</head>
<body>
    @foreach ($sections as $section)
        @include($section->view(), $section->data() + ['images' => $images])
    @endforeach
</body>
</html>
