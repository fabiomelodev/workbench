<?php

namespace App\Helpers;

class FormatCurrency
{
    public static function getFormatCurrency($value): string
    {
        // if (str_contains($value, ',')) {
        //     return 'R$ ' . $value;
        // }

        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    public static function getFormatValuePercentage(string $value, string $meta): string
    {
        $percentage = ((float) $value * 100) / (float) $meta;

        return (string) number_format($percentage, 2, '.', '');
    }
}
