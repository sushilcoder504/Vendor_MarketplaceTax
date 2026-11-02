<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\MarketplaceTax\Model\TaxSourcingResolver;

class TaxSourcingResolverTest extends TestCase
{
    private TaxSourcingResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = $this->getMockBuilder(TaxSourcingResolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['resolve'])
            ->getMock();
    }

    public function testTexasOriginSourcingUsesDallasWarehouse(): void
    {
        $this->resolver->method('resolve')
            ->willReturn([
                'state' => 'TX',
                'zip' => '75201',
                'source' => 'ORIGIN',
            ]);

        $result = $this->resolver->resolve(
            'TX',
            '77002',
            'ups_ground',
            'TX',
            '75201'
        );

        $this->assertSame('75201', $result['zip']);
        $this->assertSame('ORIGIN', $result['source']);
    }

    public function testDestinationSourcingUsesCustomerAddress(): void
    {
        $this->resolver->method('resolve')
            ->willReturn([
                'state' => 'NY',
                'zip' => '10001',
                'source' => 'DESTINATION',
            ]);

        $result = $this->resolver->resolve(
            'NY',
            '10001',
            'fedex_express',
            'TX',
            '75201'
        );

        $this->assertSame('NY', $result['state']);
        $this->assertSame('10001', $result['zip']);
        $this->assertSame('DESTINATION', $result['source']);
    }

    public function testStorePickupUsesAustinStoreAddress(): void
    {
        $this->resolver->method('resolve')
            ->willReturn([
                'state' => 'TX',
                'zip' => '78701',
                'source' => 'PICKUP',
                'shipping_amount' => 0.00,
            ]);

        $result = $this->resolver->resolve(
            'TX',
            '77002',
            'store_pickup',
            'TX',
            '75201'
        );

        $this->assertSame('78701', $result['zip']);
        $this->assertSame('PICKUP', $result['source']);
        $this->assertEquals(0.00, $result['shipping_amount']);
    }
}