<?php

if (! function_exists('money')) {
    /**
     * Formatea un importe en pesos argentinos: $ 1.234,56
     */
    function money(float|int|string|null $amount): string
    {
        return '$ '.number_format((float) $amount, 2, ',', '.');
    }
}

if (! function_exists('price_tag')) {
    /**
     * Precio para piezas de marketing (historias, catálogo): redondeado, como en el diseño: 45,000
     */
    function price_tag(float|int|string|null $amount): string
    {
        return number_format(round((float) $amount), 0, '.', ',');
    }
}

if (! function_exists('percent')) {
    /**
     * Formatea un porcentaje sin ceros de más: 21 -> 21%, 10.5 -> 10,5%
     */
    function percent(float|int|string|null $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',').'%';
    }
}

if (! function_exists('shop_price')) {
    /**
     * Precio en la tienda online, sin centavos como en el diseño: $12.500
     */
    function shop_price(float|int|string|null $amount): string
    {
        return '$'.number_format(round((float) $amount), 0, ',', '.');
    }
}
