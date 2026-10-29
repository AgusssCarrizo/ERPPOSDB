<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Genera un slug único a partir del nombre cuando no se cargó uno.
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::saving(function (self $model) {
            if (filled($model->slug)) {
                return;
            }

            $base = Str::slug($model->name);
            $slug = $base;
            $i = 2;

            while ($model->slugExists($slug)) {
                $slug = "{$base}-{$i}";
                $i++;
            }

            $model->slug = $slug;
        });
    }

    private function slugExists(string $slug): bool
    {
        $query = static::query();

        // Los eliminados también ocupan el slug (la columna es única).
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            $query->withTrashed();
        }

        return $query->where('slug', $slug)->whereKeyNot($this->getKey())->exists();
    }
}
