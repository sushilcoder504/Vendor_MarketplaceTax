<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Api\Data;

interface TaxResultInterface
{
    public function getTaxAmount(): float;

    public function getReason(): string;

    public function isTaxable(): bool;
}