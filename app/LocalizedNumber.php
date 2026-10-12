<?php

namespace App;

class LocalizedNumber
{
    /**
     * Format a number for display in the current locale.
     *
     * The intl extension is not installed here, so this maps the plain
     * number_format() output instead of using NumberFormatter: Arabic gets
     * Eastern digits with ٫/٬ separators, French gets a comma decimal with
     * space grouping, and every other locale keeps number_format() as-is.
     */
    public static function format(int|float|string|null $value, int $decimals = 0): string
    {
        $plain = number_format((float) $value, $decimals);

        return match (app()->getLocale()) {
            'ar' => strtr($plain, [
                '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
                '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
                '.' => '٫', ',' => '٬',
            ]),
            'fr' => number_format((float) $value, $decimals, ',', ' '),
            default => $plain,
        };
    }
}
