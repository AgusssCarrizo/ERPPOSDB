<?php

namespace App\Catalog;

use Illuminate\Support\HtmlString;

class Typography
{
    /**
     * Texto en mayúsculas para títulos. Los números van en otra fuente
     * (clase "num"), porque los de Playfair son más bajos que las mayúsculas.
     */
    public static function title(string $text): HtmlString
    {
        $escaped = e(mb_strtoupper($text));

        return new HtmlString(preg_replace('/\d+/', '<span class="num">$0</span>', $escaped));
    }
}
