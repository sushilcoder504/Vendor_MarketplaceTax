<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Api;

interface TaxRuleRepositoryInterface
{
    public function getApplicableRules(
        string $category,
        string $country,
        string $state,
        string $zip,
        string $date
    ): array;

    public function save(array $data): int;
}