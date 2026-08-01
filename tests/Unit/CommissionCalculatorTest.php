<?php

namespace Tests\Unit;

use App\Domain\Marketplace\Services\CommissionCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_commission_using_integer_money_values(): void
    {
        $calculator = new CommissionCalculator;

        $this->assertSame(4_800, $calculator->calculate(60_000, 800));
    }

    #[Test]
    public function it_rejects_invalid_commission_rates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CommissionCalculator)->calculate(10_000, 10_001);
    }
}
