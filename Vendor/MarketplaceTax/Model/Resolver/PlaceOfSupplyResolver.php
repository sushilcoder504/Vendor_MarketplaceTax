<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model\Resolver;

use Vendor\MarketplaceTax\Model\Config;

class PlaceOfSupplyResolver
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function resolve(array $input): array
    {
        $method = $input['shipping_method'] ?? '';

        if ($method === 'international') {
            return [
                'country' => $input['destination']['country'] ?? '',
                'state' => $input['destination']['state'] ?? '',
                'zip' => $input['destination']['zip'] ?? '',
                'city' => $input['destination']['city'] ?? '',
                'reason' => 'EXPORT'
            ];
        }

        if ($method === 'store_pickup') {
            return $this->config->getPickupAddress();
        }

        $destination = $input['destination'];
        $origin = $input['origin'] ?? $this->config->getWarehouseAddress();

        if (
            $destination['country'] === 'US'
            && $origin['country'] === 'US'
            && $destination['state'] === $origin['state']
            && $this->config->getSourcingType($destination['state']) === 'origin'
        ) {
            return $origin;
        }

        return $destination;
    }
}