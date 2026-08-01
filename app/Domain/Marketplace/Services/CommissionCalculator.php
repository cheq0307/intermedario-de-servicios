<?php

namespace App\Domain\Marketplace\Services;

use InvalidArgumentException;

final class CommissionCalculator
{
    /**
     * Money is represented as integer cents and rates as basis points.
     * Example: 800 basis points = 8.00%.
     */
    public function calculate(int $grossAmount, int $rateBasisPoints): int
    {
        if ($grossAmount < 0) {
            throw new InvalidArgumentException('Gross amount cannot be negative.');
        }

        if ($rateBasisPoints < 0 || $rateBasisPoints > 10_000) {
            throw new InvalidArgumentException('Commission rate must be between 0 and 10,000 basis points.');
        }

        return (int) round($grossAmount * $rateBasisPoints / 10_000);
    }
}
