<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Test\Unit\Model;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Vendor\MarketplaceTax\Model\TaxabilityResolver;

class TaxabilityResolverTest extends TestCase
{
    private TaxabilityResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        // Replace constructor dependencies with mocks
        // matching your actual TaxabilityResolver.
        $this->resolver = $this->getMockBuilder(TaxabilityResolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['resolve'])
            ->getMock();
    }

    public function testZipRuleWinsOverStateAndCountry(): void
    {
        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with(
                'APPAREL',
                'US',
                'NY',
                '10001',
                100.00,
                $this->isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn([
                'rule_code' => 'NY_ZIP_APPAREL',
                'treatment' => 'EXEMPT',
                'scope' => 'ZIP',
            ]);

        $result = $this->resolver->resolve(
            'APPAREL',
            'US',
            'NY',
            '10001',
            100.00,
            new DateTimeImmutable('2026-06-01')
        );

        $this->assertSame('NY_ZIP_APPAREL', $result['rule_code']);
        $this->assertSame('EXEMPT', $result['treatment']);
    }

    public function testStateRuleWinsOverCountryRule(): void
    {
        $this->resolver->method('resolve')
            ->willReturn([
                'rule_code' => 'TX_GROC',
                'treatment' => 'EXEMPT',
                'scope' => 'STATE',
            ]);

        $result = $this->resolver->resolve(
            'GROC_PKG',
            'US',
            'TX',
            '75201',
            6.00,
            new DateTimeImmutable('2026-06-01')
        );

        $this->assertSame('TX_GROC', $result['rule_code']);
        $this->assertSame('STATE', $result['scope']);
    }

    public function testHigherPriorityWinsWhenSpecificityIsEqual(): void
    {
        $this->resolver->method('resolve')
            ->willReturn([
                'rule_code' => 'TX_HIGH_PRIORITY',
                'priority' => 50,
                'treatment' => 'EXEMPT',
            ]);

        $result = $this->resolver->resolve(
            'APPAREL',
            'US',
            'TX',
            '75201',
            80.00,
            new DateTimeImmutable('2026-08-08')
        );

        $this->assertSame(50, $result['priority']);
        $this->assertSame('TX_HIGH_PRIORITY', $result['rule_code']);
    }

    public function testTaxHolidayAppliesOnAugustEighth(): void
    {
        $this->resolver->method('resolve')
            ->willReturn([
                'rule_code' => 'TX_HOLIDAY_APPAREL',
                'treatment' => 'EXEMPT',
            ]);

        $result = $this->resolver->resolve(
            'APPAREL',
            'US',
            'TX',
            '75201',
            60.00,
            new DateTimeImmutable('2026-08-08')
        );

        $this->assertSame('EXEMPT', $result['treatment']);
    }

    public function testTaxHolidayDoesNotApplyOnAugustTenth(): void
    {
        $this->resolver->method('resolve')
            ->willReturn([
                'rule_code' => 'US_DEFAULT',
                'treatment' => 'TAXABLE',
            ]);

        $result = $this->resolver->resolve(
            'APPAREL',
            'US',
            'TX',
            '75201',
            60.00,
            new DateTimeImmutable('2026-08-10')
        );

        $this->assertSame('TAXABLE', $result['treatment']);
    }
}