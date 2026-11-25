<?php

if (! function_exists('money')) {
    /** Importe en pesos con formato argentino: $ 1.234,50 */
    function money(float|string|null $amount): string
    {
        return '$ '.number_format((float) $amount, 2, ',', '.');
    }
}

if (! function_exists('qty')) {
    /** Cantidad sin ceros de sobra: 2 · 1,5 · 0,25 */
    function qty(float|string|null $value): string
    {
        $formatted = number_format((float) $value, 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
