<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Api;

interface TaxCalculatorInterface
{
    /**
     * Calculate tax for a quote item.
     *
     * @param array $input
     * @return array
     */
    public function calculate(array $input): array;
}