<?php

namespace App\Support;

/**
 * Base-currency helpers. The foundation reports in one currency
 * (config "app.currency"); every admin total is rendered with it.
 */
class Money
{
    /**
     * @var array<string, string>
     */
    private const SYMBOLS = [
        'USD' => '$',
        'GHS' => 'GH₵',
        'EUR' => '€',
        'GBP' => '£',
        'NGN' => '₦',
    ];

    public static function baseCurrency(): string
    {
        return strtoupper((string) config('app.currency', 'USD'));
    }

    public static function symbol(): string
    {
        $currency = self::baseCurrency();

        return self::SYMBOLS[$currency] ?? $currency.' ';
    }

    /**
     * Format an amount in major units (e.g. dollars) with the base currency symbol.
     */
    public static function format(int|float $amount, int $decimals = 0): string
    {
        return self::symbol().number_format($amount, $decimals);
    }

    /**
     * Format an amount stored in minor units (cents) with the base currency symbol.
     */
    public static function fromCents(int $cents, int $decimals = 0): string
    {
        return self::format($cents / 100, $decimals);
    }
}
