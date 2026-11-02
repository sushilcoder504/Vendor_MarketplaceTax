<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Test\Unit\Model;

use PHPUnit\Framework\TestCase;

class TaxRoundingTest extends TestCase
{
    public function testS1ShippingRoundsEachJurisdiction(): void
    {
        $shipping = 50.00;

        $state = round($shipping * 0.0625, 2, PHP_ROUND_HALF_UP);
        $city = round($shipping * 0.0100, 2, PHP_ROUND_HALF_UP);
        $special = round($shipping * 0.0100, 2, PHP_ROUND_HALF_UP);

        $this->assertEquals(3.13, $state);
        $this->assertEquals(0.50, $city);
        $this->assertEquals(0.50, $special);

        $this->assertEquals(4.13, $state + $city + $special);
    }

    public function testS3ShippingRoundsEachJurisdiction(): void
    {
        $shipping = 15.00;

        $state = round($shipping * 0.04, 2, PHP_ROUND_HALF_UP);
        $city = round($shipping * 0.045, 2, PHP_ROUND_HALF_UP);
        $special = round($shipping * 0.00375, 2, PHP_ROUND_HALF_UP);

        $this->assertEquals(0.60, $state);
        $this->assertEquals(0.68, $city);
        $this->assertEquals(0.06, $special);

        $this->assertEquals(1.34, $state + $city + $special);
    }

    public function testRoundingUsesHalfUp(): void
    {
        $this->assertEquals(
            1.01,
            round(1.005, 2, PHP_ROUND_HALF_UP)
        );
    }
}