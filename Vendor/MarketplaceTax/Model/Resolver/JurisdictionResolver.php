<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model\Resolver;

use Vendor\MarketplaceTax\Api\RateRepositoryInterface;

class JurisdictionResolver
{
    public function __construct(
        private readonly RateRepositoryInterface $rateRepository
    ) {
    }

    public function resolve(array $address, string $date): array
    {
        $rates = $this->rateRepository->getByLocation(
            $address['country'],
            $address['state'],
            $address['zip'],
            $date
        );

        $result = [];

        foreach ($rates as $rate) {
            $result[] = [
                'rate_code' => $rate['rate_code'],
                'level' => $rate['level'],
                'name' => $rate['jurisdiction_name'],
                'rate' => (float)$rate['rate']
            ];
        }

        return $result;
    }
}