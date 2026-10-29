<?php

namespace App\Services;

use App\Models\Product;
use GdImage;
use Illuminate\Support\Facades\Storage;

/**
 * Genera la historia de Instagram (1080x1920) de un producto a partir de la plantilla
 * resources/story/base.png (diseño de Canva sin título, foto ni precio).
 *
 * Las posiciones están medidas en píxeles sobre la plantilla. Si cambia el diseño,
 * se reemplaza base.png y se ajustan estas constantes.
 */
class ProductStoryImage
{
    private const TEMPLATE = 'story/base.png';

    private const TITLE_FONT = 'fonts/PlayfairDisplay-Regular.ttf';

    /** Playfair dibuja los números "chicos" (estilo antiguo); para los dígitos se usa Bodoni Moda. */
    private const TITLE_DIGITS_FONT = 'fonts/BodoniModa.ttf';

    private const TITLE_COLOR = [0x58, 0x35, 0x1B];

    /** Centro horizontal del diseño (el original está corrido unos px a la derecha). */
    private const CENTER_X = 556;

    /** Zona del título: arriba, abajo y ancho máximo de cada línea. */
    private const TITLE_TOP = 150;

    private const TITLE_BOTTOM = 355;

    private const TITLE_MAX_WIDTH = 680;

    private const TITLE_MAX_LINES = 3;

    private const TITLE_SIZE = 46;          // tamaño de fuente (pt) del diseño original

    private const TITLE_MIN_SIZE = 34;      // si no entra, se achica hasta acá y después se corta con "…"

    private const TITLE_TRACKING = 0.13;    // espaciado entre letras, en proporción del tamaño

    private const TITLE_LINE_HEIGHT = 1.08;

    /** Caja donde va la foto del producto (se ajusta sin deformar). */
    private const PHOTO_BOX = ['x' => 257, 'y' => 396, 'width' => 598, 'height' => 922];

    /** Interior del marco del precio. */
    private const PRICE_CENTER = ['centerX' => 734, 'centerY' => 1445];

    public function __construct(private readonly PriceTagRenderer $priceTag = new PriceTagRenderer) {}

    /**
     * Devuelve la imagen como PNG (binario).
     */
    public function render(Product $product): string
    {
        $image = imagecreatefrompng(resource_path(self::TEMPLATE));
        imagealphablending($image, true);

        $this->drawPhoto($image, $product);
        $this->drawTitle($image, mb_strtoupper($product->name));
        $this->priceTag->draw($image, $this->formatPrice($product->final_price), ...self::PRICE_CENTER);

        ob_start();
        imagepng($image, null, 6);

        return ob_get_clean();
    }

    /**
     * Precio con el mismo formato que el diseño: 45,000
     */
    public function formatPrice(float|string $price): string
    {
        return price_tag($price);
    }

    private function drawTitle(GdImage $image, string $title): void
    {
        $color = $this->color($image, self::TITLE_COLOR);

        // Busca el tamaño más grande con el que el título entra en las líneas disponibles.
        $size = self::TITLE_SIZE;
        $lines = $this->wrap($title, $size);

        while ($size > self::TITLE_MIN_SIZE && ! $this->titleFits($lines, $size)) {
            $size -= 2;
            $lines = $this->wrap($title, $size);
        }

        // Si ni achicando entra, la última línea termina en "…".
        if (count($lines) > self::TITLE_MAX_LINES) {
            $rest = implode(' ', array_slice($lines, self::TITLE_MAX_LINES - 1));
            $lines = [...array_slice($lines, 0, self::TITLE_MAX_LINES - 1), $this->ellipsize($rest, $size)];
        }

        $lineHeight = $size * 4 / 3 * self::TITLE_LINE_HEIGHT;   // pt -> px
        $capHeight = $this->inkHeight('H', $size, resource_path(self::TITLE_FONT));

        // Centra el bloque de líneas verticalmente dentro de la zona del título.
        $blockHeight = $lineHeight * (count($lines) - 1) + $capHeight;
        $baseline = self::TITLE_TOP + (self::TITLE_BOTTOM - self::TITLE_TOP - $blockHeight) / 2 + $capHeight;

        foreach ($lines as $line) {
            // Una palabra sola más ancha que la caja se achica solo en su línea.
            $lineSize = $size;
            while ($lineSize > 12 && $this->trackedWidth($line, $lineSize) > self::TITLE_MAX_WIDTH) {
                $lineSize--;
            }

            $x = self::CENTER_X - $this->trackedWidth($line, $lineSize) / 2;
            $this->drawTracked($image, $line, $lineSize, $x, $baseline, $color);
            $baseline += $lineHeight;
        }
    }

    private function drawPhoto(GdImage $image, Product $product): void
    {
        $box = self::PHOTO_BOX;
        $photo = $this->loadPhoto($product);

        if (! $photo) {
            $this->drawPlaceholder($image);

            return;
        }

        // "contain": entra completa en la caja, centrada, sin deformarse.
        $scale = min($box['width'] / imagesx($photo), $box['height'] / imagesy($photo));
        $width = (int) round(imagesx($photo) * $scale);
        $height = (int) round(imagesy($photo) * $scale);
        $x = $box['x'] + intdiv($box['width'] - $width, 2);
        $y = $box['y'] + intdiv($box['height'] - $height, 2);

        imagecopyresampled($image, $photo, $x, $y, 0, 0, $width, $height, imagesx($photo), imagesy($photo));
    }

    private function drawPlaceholder(GdImage $image): void
    {
        $box = self::PHOTO_BOX;
        $text = 'SIN IMAGEN';

        imagefilledrectangle($image, $box['x'], $box['y'], $box['x'] + $box['width'], $box['y'] + $box['height'],
            $this->color($image, [0xF5, 0xF1, 0xEC]));

        $x = self::CENTER_X - $this->trackedWidth($text, 22) / 2;
        $y = $box['y'] + $box['height'] / 2;
        $this->drawTracked($image, $text, 22, $x, $y, $this->color($image, [0xB5, 0xA4, 0x95]));
    }

    private function loadPhoto(Product $product): ?GdImage
    {
        if (! $product->image_path || ! Storage::disk('public')->exists($product->image_path)) {
            return null;
        }

        $photo = @imagecreatefromstring(Storage::disk('public')->get($product->image_path));

        return $photo ?: null;
    }

    /**
     * Parte el texto en líneas que entren en TITLE_MAX_WIDTH.
     *
     * @return list<string>
     */
    private function wrap(string $text, int $size): array
    {
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $candidate = $current === '' ? $word : "{$current} {$word}";

            if ($current !== '' && $this->trackedWidth($candidate, $size) > self::TITLE_MAX_WIDTH) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        $lines[] = $current;

        return $lines;
    }

    private function titleFits(array $lines, int $size): bool
    {
        if (count($lines) > self::TITLE_MAX_LINES) {
            return false;
        }

        foreach ($lines as $line) {
            if ($this->trackedWidth($line, $size) > self::TITLE_MAX_WIDTH) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recorta el texto (por palabras) hasta que entre con "…" al final.
     */
    private function ellipsize(string $text, int $size): string
    {
        while (mb_strlen($text) > 1 && $this->trackedWidth($text.'…', $size) > self::TITLE_MAX_WIDTH) {
            $text = str_contains($text, ' ')
                ? mb_substr($text, 0, mb_strrpos($text, ' '))
                : mb_substr($text, 0, -1);
        }

        return rtrim($text, ' ,.-').'…';
    }

    private function drawTracked(GdImage $image, string $text, int $size, float $x, float $y, int $color): void
    {
        foreach (mb_str_split($text) as $char) {
            imagettftext($image, $size, 0, (int) round($x), (int) round($y), $color, $this->titleFontFor($char), $char);
            $x += $this->advance($char, $size) + $size * self::TITLE_TRACKING;
        }
    }

    private function trackedWidth(string $text, int $size): float
    {
        $chars = mb_str_split($text);
        $width = array_sum(array_map(fn ($char) => $this->advance($char, $size), $chars));

        return $width + $size * self::TITLE_TRACKING * (count($chars) - 1);
    }

    /**
     * Ancho que ocupa un carácter (GD no expone el "advance", se aproxima con la caja de tinta).
     */
    private function advance(string $char, int $size): float
    {
        if ($char === ' ') {
            return $size * 0.35;
        }

        return $this->textWidth($char, $size, $this->titleFontFor($char)) + $size * 0.08;
    }

    private function titleFontFor(string $char): string
    {
        return resource_path(ctype_digit($char) ? self::TITLE_DIGITS_FONT : self::TITLE_FONT);
    }

    private function textWidth(string $text, int $size, string $font): float
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box[2] - $box[0];
    }

    private function inkHeight(string $text, int $size, string $font): float
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box[1] - $box[7];
    }

    private function color(GdImage $image, array $rgb): int
    {
        return imagecolorallocate($image, ...$rgb);
    }
}
