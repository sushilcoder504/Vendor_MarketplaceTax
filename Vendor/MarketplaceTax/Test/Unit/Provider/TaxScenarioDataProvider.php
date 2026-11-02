<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Test\Unit\Provider;

class TaxScenarioDataProvider
{
    public static function scenarios(): array
    {
        return [
            'S1 Dallas shipping' => [
                'TX',
                '75201',
                'ups_ground',
                '2026-06-01',
                103.13,
            ],

            'S2 Texas origin sourcing' => [
                'TX',
                '77002',
                'ups_ground',
                '2026-06-01',
                103.13,
            ],

            'S3 New York mixed cart' => [
                'NY',
                '10001',
                'fedex_express',
                '2026-06-01',
                24.42,
            ],

            'S4 Oregon no sales tax' => [
                'OR',
                '97201',
                'ups_ground',
                '2026-06-01',
                0.00,
            ],

            'S5 Austin store pickup' => [
                'TX',
                '78701',
                'store_pickup',
                '2026-06-01',
                96.00,
            ],

            'S6 NY apparel discount' => [
                'NY',
                '10001',
                'fedex_express',
                '2026-06-01',
                0.00,
            ],

            'S7 Texas tax holiday' => [
                'TX',
                '75201',
                'ups_ground',
                '2026-08-08',
                0.00,
            ],

            'S7 Texas after tax holiday' => [
                'TX',
                '75201',
                'ups_ground',
                '2026-08-10',
                4.95,
            ],

            'S8 Canada export' => [
                'CA',
                'M5V1A1',
                'international',
                '2026-06-01',
                0.00,
            ],
        ];
    }
}