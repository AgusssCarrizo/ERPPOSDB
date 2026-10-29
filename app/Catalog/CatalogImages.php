<?php

namespace App\Catalog;

use App\Services\PriceTagRenderer;
use GdImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Prepara las imágenes para el PDF: las achica a la medida justa (las fotos originales
 * pueden pesar varios MB) y las devuelve como data URI, que dompdf incrusta sin problemas.
 * El resultado queda cacheado en storage/framework/cache/catalog.
 */
class CatalogImages
{
    /** @var array<string, string> */
    private array $assets = [];

    public function __construct(private readonly PriceTagRenderer $priceTags = new PriceTagRenderer) {}

    /**
     * Imagen completa dentro de un lienzo blanco de $width x $height (no se recorta).
     */
    public function contain(?string $path, int $width, int $height): ?string
    {
        return $this->prepare($path, $width, $height, 'contain');
    }

    /**
     * Imagen que cubre todo el $width x $height (se recortan los bordes que sobran).
     */
    public function cover(?string $path, int $width, int $height): ?string
    {
        return $this->prepare($path, $width, $height, 'cover');
    }

    /**
     * El marco del diseño con el precio adentro (ver PriceTagRenderer).
     */
    public function priceTag(string $price): string
    {
        return $this->assets["price:{$price}"] ??= 'data:image/png;base64,'
            .base64_encode($this->priceTags->png($price));
    }

    /**
     * Elementos fijos del diseño (resources/catalog/*.png): ramitas, pie, logo.
     */
    public function asset(string $name): string
    {
        return $this->assets[$name] ??= 'data:image/png;base64,'
            .base64_encode(File::get(resource_path("catalog/{$name}.png")));
    }

    private function prepare(?string $path, int $width, int $height, string $mode): ?string
    {
        $disk = Storage::disk('public');

        if (! $path || ! $disk->exists($path)) {
            return null;
        }

        $cacheFile = storage_path('framework/cache/catalog/'
            .md5(implode('|', [$path, $disk->lastModified($path), $width, $height, $mode])).'.jpg');

        if (! File::exists($cacheFile)) {
            $source = @imagecreatefromstring($disk->get($path));

            if (! $source) {
                return null;
            }

            File::ensureDirectoryExists(dirname($cacheFile));
            imagejpeg($this->resize($source, $width, $height, $mode), $cacheFile, 85);
        }

        return 'data:image/jpeg;base64,'.base64_encode(File::get($cacheFile));
    }

    private function resize(GdImage $source, int $width, int $height, string $mode): GdImage
    {
        [$sourceWidth, $sourceHeight] = [imagesx($source), imagesy($source)];

        $scale = $mode === 'cover'
            ? max($width / $sourceWidth, $height / $sourceHeight)
            : min($width / $sourceWidth, $height / $sourceHeight);

        $targetWidth = (int) round($sourceWidth * $scale);
        $targetHeight = (int) round($sourceHeight * $scale);

        $canvas = imagecreatetruecolor($width, $height);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));   // fondo blanco para PNG transparentes
        imagecopyresampled(
            $canvas, $source,
            intdiv($width - $targetWidth, 2), intdiv($height - $targetHeight, 2), 0, 0,
            $targetWidth, $targetHeight, $sourceWidth, $sourceHeight,
        );

        return $canvas;
    }
}
