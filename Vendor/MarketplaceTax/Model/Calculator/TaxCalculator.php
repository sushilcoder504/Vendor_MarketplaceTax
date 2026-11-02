<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model\Calculator;

use Vendor\MarketplaceTax\Api\TaxCalculatorInterface;
use Vendor\MarketplaceTax\Model\Config;
use Vendor\MarketplaceTax\Model\Resolver\JurisdictionResolver;
use Vendor\MarketplaceTax\Model\Resolver\TaxabilityResolver;

class TaxCalculator implements TaxCalculatorInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly TaxabilityResolver $taxabilityResolver,
        private readonly JurisdictionResolver $jurisdictionResolver
    ) {
    }

    public function calculate(array $input): array
    {
        $address = $input['address'];

        $country = $address['country'];
        $state = $address['state'] ?? '';

        $unitPrice = (float)$input['unit_price'];
        $quantity = (float)$input['quantity'];

        $result = [
            'tax_amount' => 0.0,
            'reason' => null,
            'rule_code' => null,
            'breakdown' => []
        ];

        if ($country !== 'US') {
            $result['reason'] = 'EXPORT';
            return $result;
        }

        if (in_array($state, $this->config->getNoTaxStates(), true)) {
            $result['reason'] = 'NO_SALES_TAX';
            return $result;
        }

        $category = $input['tax_category']
            ?: $this->config->getDefaultCategory();

        $date = $input['date'];

        $taxability = $this->taxabilityResolver->resolve(
            $category,
            $address,
            $unitPrice,
            $date
        );

        $result['rule_code'] = $taxability['rule_code'];

        if ($taxability['treatment'] === 'EXEMPT') {
            $result['reason'] = $taxability['reason'];
            return $result;
        }

        $taxableAmount = round(
            $unitPrice * $quantity,
            2,
            PHP_ROUND_HALF_UP
        );

        $rates = $this->jurisdictionResolver->resolve(
            $address,
            $date
        );

        foreach ($rates as $rate) {
            $taxAmount = round(
                $taxableAmount * ($rate['rate'] / 100),
                2,
                PHP_ROUND_HALF_UP
            );

            $result['breakdown'][] = [
                'rule_code' => $taxability['rule_code'],
                'level' => $rate['level'],
                'jurisdiction' => $rate['name'],
                'rate' => $rate['rate'],
                'taxable_amount' => $taxableAmount,
                'tax_amount' => $taxAmount
            ];

            $result['tax_amount'] += $taxAmount;
        }

        $result['reason'] = 'TAXABLE';

        return $result;
    }
}