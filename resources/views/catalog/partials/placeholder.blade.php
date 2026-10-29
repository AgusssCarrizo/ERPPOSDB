{{-- Recuadro para cuando falta la foto. Es una tabla para centrar el texto (dompdf no tiene flexbox). Medidas en mm. --}}
<table class="abs placeholder" style="left: {{ $left }}mm; top: {{ $top }}mm; width: {{ $width }}mm; border-collapse: collapse;">
    <tr>
        <td style="height: {{ $height }}mm; vertical-align: middle; text-align: center;">{{ $text }}</td>
    </tr>
</table>
