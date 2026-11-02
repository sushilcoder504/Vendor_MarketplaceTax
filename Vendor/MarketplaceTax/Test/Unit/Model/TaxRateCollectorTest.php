<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Test\Unit\Model;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Vendor\MarketplaceTax\Model\TaxRateCollector;

class TaxRateCollectorTest extends TestCase
{
    private TaxRateCollector $collector;

    protected function setUp(): void
    {
        $this->collector = $this->getMockBuilder(TaxRateCollector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['collect'])
            ->getMock();
    }

    public function testDallasZipReturnsAllJurisdictions(): void
    {
        $expected = [
            [
                'rate_code' => 'TX_STATE',
                'level' => 'STATE',
                'rate' => 6.2500,
            ],
            [
                'rate_code' => 'TX_DALLAS_CITY',
                'level' => 'CITY',
                'rate' => 1.0000,
            ],
            [
                'rate_code' => 'TX_DALLAS_SPD',
                'level' => 'SPECIAL',
                'rate' => 1.0000,
            ],
        ];

        $this->collector->method('collect')
            ->willReturn($expected);

        $result = $this->collector->collect(
            'US',
            'TX',
            '75201',
            new DateTimeImmutable('2026-06-01')
        );

        $this->assertCount(3, $result);

        $this->assertEquals(
            ['STATE', 'CITY', 'SPECIAL'],
            array_column($result, 'level')
        );

        $this->assertEqualsWithDelta(
            8.25,
            array_sum(array_column($result, 'rate')),
            0.0001
        );
    }

    public function testNewYorkZipReturnsCorrectRates(): void
    {
        $expected = [
            ['level' => 'STATE', 'rate' => 4.0000],
            ['level' => 'CITY', 'rate' => 4.5000],
            ['level' => 'SPECIAL', 'rate' => 0.3750],
        ];

        $this->collector->method('collect')
            ->willReturn($expected);

        $result = $this->collector->collect(
            'US',
            'NY',
            '10001',
            new DateTimeImmutable('2026-06-01')
        );

        $this->assertCount(3, $result);

        $this->assertEqualsWithDelta(
            8.875,
            array_sum(array_column($result, 'rate')),
            0.0001
        );
    }

    public function testInactiveRatesAreNotReturned(): void
    {
        $this->collector->method('collect')
            ->willReturn([]);

        $result = $this->collector->collect(
            'US',
            'TX',
            '75201',
            new DateTimeImmutable('2025-01-01')
        );

        $this->assertEmpty($result);
    }
}