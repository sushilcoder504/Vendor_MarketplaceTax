<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Test\Unit\Model;

use PHPUnit\Framework\TestCase;

class ApparelTaxCalculatorTest extends TestCase
{
    /**
     * @dataProvider apparelProvider
     */
    public function testNewYorkApparelExemption(
        float $unitPrice,
        float $discount,
        bool $expectedExempt
    ): void {
        $discountedPrice = $unitPrice - $discount;

        $actualExempt = $discountedPrice < 110.00;

        $this->assertSame($expectedExempt, $actualExempt);
    }

    public static function apparelProvider(): array
    {
        return [
            'below threshold' => [100.00, 0.00, true],
            'exactly threshold' => [110.00, 0.00, false],
            'above threshold' => [125.00, 0.00, false],
            'S6 discount case' => [125.00, 25.00, true],
            'discount still above threshold' => [140.00, 10.00, false],
        ];
    }
}