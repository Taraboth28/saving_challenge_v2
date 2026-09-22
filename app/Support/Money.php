<?php

namespace App\Support;

/**
 * Converts between decimal amounts and integer cents so sums never drift.
 */
final class Money
{
    public static function toCents(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
