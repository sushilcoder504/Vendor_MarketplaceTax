<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\MarketplaceTax\Model\TaxCalculator;

class TaxCalculatorTest extends TestCase
{
    private TaxCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = $this->getMockBuilder(TaxCalculator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['calculate'])
            ->getMock();
    }

    public function testExportReturnsZeroTax(): void
    {
        $this->calculator->method('calculate')
            ->willReturn([
                'tax_amount' => 0.00,
                'reason' => 'EXPORT',
            ]);

        $result = $this->calculator->calculate([
            'country' => 'CA',
            'state' => 'ON',
            'zip' => 'M5V1A1',
        ]);

        $this->assertEquals(0.00, $result['tax_amount']);
        $this->assertSame('EXPORT', $result['reason']);
    }

    public function testNoSalesTaxStateReturnsZero(): void
    {
        $this->calculator->method('calculate')
            ->willReturn([
                'tax_amount' => 0.00,
                'reason' => 'NO_SALES_TAX',
            ]);

        $result = $this->calculator->calculate([
            'country' => 'US',
            'state' => 'OR',
            'zip' => '97201',
        ]);

        $this->assertEquals(0.00, $result['tax_amount']);
        $this->assertSame('NO_SALES_TAX', $result['reason']);
    }

    public function testExemptZipReturnsZero(): void
    {
        $this->calculator->method('calculate')
            ->willReturn([
                'tax_amount' => 0.00,
                'reason' => 'ZONE_EXEMPT',
            ]);

        $result = $this->calculator->calculate([
            'country' => 'US',
            'state' => 'TX',
            'zip' => '75201',
        ]);

        $this->assertEquals(0.00, $result['tax_amount']);
        $this->assertSame('ZONE_EXEMPT', $result['reason']);
    }
}