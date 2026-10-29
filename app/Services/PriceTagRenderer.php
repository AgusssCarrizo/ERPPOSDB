<?php

namespace App\Services;

use GdImage;

/**
 * Dibuja el precio centrado dentro del marco del diseño. Lo usan la historia de
 * Instagram (sobre su plantilla) y el catálogo PDF (como imagen suelta).
 *
 * Se dibuja con GD y no como texto del PDF porque dompdf ubica la línea base según
 * la "caja" completa de la fuente (Hind incluye escrituras indias muy altas) y los
 * números quedaban corridos hacia abajo. Acá se centra sobre la tinta real de los dígitos.
 */
class PriceTagRenderer
{
    private const FONT = 'fonts/Hind-SemiBold.ttf';

    private const FRAME = 'catalog/price-frame.png';

    /** Medidas en píxeles de la plantilla original (escala 1). */
    private const SIZE = 56;

    private const MAX_WIDTH = 300;

    /** Centro del interior del marco dentro de price-frame.png. */
    private const FRAME_CENTER = ['x' => 220, 'y' => 67];

    /**
     * Escribe el precio con su centro en ($centerX, $centerY).
     */
    public function draw(GdImage $image, string $price, float $centerX, float $centerY, float $scale = 1): void
    {
        $font = resource_path(self::FONT);
        $size = self::SIZE * $scale;

        while ($size > 20 * $scale && $this->width($price, $size, $font) > self::MAX_WIDTH * $scale) {
            $size -= 2 * $scale;
        }

        $box = imagettfbbox($size, 0, $font, $price);
        $x = $centerX - ($box[2] - $box[0]) / 2 - $box[0];
        $y = $centerY + $this->digitHeight($size, $font) / 2;

        imagettftext($image, $size, 0, (int) round($x), (int) round($y), imagecolorallocate($image, 0, 0, 0), $font, $price);
    }

    /**
     * El marco con el precio adentro, como PNG. $scale 2 = el doble de resolución (para imprimir).
     */
    public function png(string $price, float $scale = 2): string
    {
        $frame = imagecreatefrompng(resource_path(self::FRAME));
        $width = (int) round(imagesx($frame) * $scale);
        $height = (int) round(imagesy($frame) * $scale);

        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagecopyresampled($image, $frame, 0, 0, 0, 0, $width, $height, imagesx($frame), imagesy($frame));

        $this->draw($image, $price, self::FRAME_CENTER['x'] * $scale, self::FRAME_CENTER['y'] * $scale, $scale);

        ob_start();
        imagepng($image, null, 9);

        return ob_get_clean();
    }

    private function width(string $text, float $size, string $font): float
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box[2] - $box[0];
    }

    /**
     * Alto de los números (sin la coma, que baja de la línea).
     */
    private function digitHeight(float $size, string $font): float
    {
        $box = imagettfbbox($size, 0, $font, '0');

        return $box[1] - $box[7];
    }
}
