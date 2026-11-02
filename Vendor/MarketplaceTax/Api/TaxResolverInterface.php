<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Api;

use Vendor\MarketplaceTax\Api\Data\TaxResultInterface;

interface TaxResolverInterface
{
    public function resolve(
        string $taxCategory,
        string $countryCode,
        string $stateCode,
        string $zipCode,
        float $unitPrice,
        string $orderDate
    ): TaxResultInterface;
}