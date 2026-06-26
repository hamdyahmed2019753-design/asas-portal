<?php

namespace App\Support;

class Money
{
    /**
     * Currency code => display symbol.
     *
     * @var array<string, string>
     */
    private const SYMBOLS = [
        'SAR' => 'ر.س',
    ];

    /**
     * Number of decimal places shown per currency (default 2).
     *
     * @var array<string, int>
     */
    private const DECIMALS = [
        'SAR' => 2,
    ];

    public static function format(int|float|string|null $amount, string $currency = 'SAR'): string
    {
        $currency = strtoupper($currency);
        $decimals = self::DECIMALS[$currency] ?? 2;
        $symbol = self::SYMBOLS[$currency] ?? $currency;

        $formatted = number_format((float) $amount, $decimals);

        return "{$formatted} {$symbol}";
    }
}
