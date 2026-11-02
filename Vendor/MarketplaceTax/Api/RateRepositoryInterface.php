<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Api;

interface RateRepositoryInterface
{
    public function getByLocation(
        string $country,
        string $state,
        string $zip,
        string $date
    ): array;

    public function save(array $data): int;
}