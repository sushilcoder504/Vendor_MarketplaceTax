<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model;

use Vendor\MarketplaceTax\Api\TaxResolverInterface;
use Vendor\MarketplaceTax\Api\Data\TaxResultInterface;
use Vendor\MarketplaceTax\Model\Data\TaxResult;

class TaxResolver implements TaxResolverInterface
{
    public function resolve(
        string $taxCategory,
        string $countryCode,
        string $stateCode,
        string $zipCode,
        float $unitPrice,
        string $orderDate
    ): TaxResultInterface {

        if ($countryCode !== 'US') {
            return new TaxResult(0.0, 'EXPORT', false);
        }

        $noTaxStates = ['OR', 'MT', 'NH', 'DE'];

        if (in_array($stateCode, $noTaxStates, true)) {
            return new TaxResult(
                0.0,
                'NO_SALES_TAX',
                false
            );
        }

        if (
            $taxCategory === 'GROC_FRESH'
            || (
                $taxCategory === 'GROC_PKG'
                && in_array($stateCode, ['TX', 'NY'], true)
            )
        ) {
            return new TaxResult(
                0.0,
                'PRODUCT_EXEMPT',
                false
            );
        }

        return new TaxResult(
            0.0,
            'TAXABLE_RATE_PENDING',
            true
        );
    }
}