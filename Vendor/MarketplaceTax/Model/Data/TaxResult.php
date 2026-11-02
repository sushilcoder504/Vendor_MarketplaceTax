<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model\Data;

use Vendor\MarketplaceTax\Api\Data\TaxResultInterface;

class TaxResult implements TaxResultInterface
{
    public function __construct(
        private readonly float $taxAmount,
        private readonly string $reason,
        private readonly bool $taxable
    ) {
    }

    public function getTaxAmount(): float
    {
        return $this->taxAmount;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function isTaxable(): bool
    {
        return $this->taxable;
    }
}