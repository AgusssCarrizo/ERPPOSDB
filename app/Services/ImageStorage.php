<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Guarda y reemplaza las fotos subidas (productos, categorías, portada de la tienda).
 *
 * En producción las fotos van a R2: si la subida falla, el disco devuelve false en vez de
 * cortar con un error. Acá se convierte en un error de validación, así el formulario
 * avisa "No se pudo guardar la foto" y no se guarda nada a medias.
 */
class ImageStorage
{
    public const FAILED_MESSAGE = 'No se pudo guardar la foto. Probá de nuevo en un rato.';

    /**
     * Sube la foto a $folder y devuelve su ruta.
     *
     * @throws ValidationException si no se pudo subir ($field = campo del formulario donde se muestra el error)
     */
    public function store(UploadedFile $file, string $folder, string $field): string
    {
        $path = $file->store($folder, 'public');

        if (! $path) {
            throw ValidationException::withMessages([$field => self::FAILED_MESSAGE]);
        }

        return $path;
    }

    /**
     * Sube la foto nueva y recién después borra la anterior: si la subida falla, la vieja sigue en su lugar.
     */
    public function replace(UploadedFile $file, string $folder, string $field, ?string $oldPath): string
    {
        $path = $this->store($file, $folder, $field);
        $this->delete($oldPath);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
