<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_DEFAULT_CATEGORY =
        'marketplace_tax/general/default_category';

    private const XML_PATH_NO_TAX_STATES =
        'marketplace_tax/general/no_tax_states';

    private const XML_PATH_ORIGIN =
        'marketplace_tax/locations/warehouse';

    private const XML_PATH_PICKUP =
        'marketplace_tax/locations/pickup';

    private const XML_PATH_SOURCING =
        'marketplace_tax/sourcing/';

    private const XML_PATH_SHIPPING =
        'marketplace_tax/shipping/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getDefaultCategory(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_CATEGORY,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getNoTaxStates(): array
    {
        $states = (string)$this->scopeConfig->getValue(
            self::XML_PATH_NO_TAX_STATES,
            ScopeInterface::SCOPE_STORE
        );

        return array_filter(array_map('trim', explode(',', $states)));
    }

    public function getWarehouseAddress(): array
    {
        return $this->getAddress(self::XML_PATH_ORIGIN);
    }

    public function getPickupAddress(): array
    {
        return $this->getAddress(self::XML_PATH_PICKUP);
    }

    public function getSourcingType(string $state): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_SOURCING . strtolower($state),
            ScopeInterface::SCOPE_STORE
        ) ?: 'destination';
    }

    public function isShippingTaxable(string $state): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_SHIPPING . strtolower($state),
            ScopeInterface::SCOPE_STORE
        );
    }

    private function getAddress(string $path): array
    {
        return [
            'country' => $this->scopeConfig->getValue(
                $path . '/country',
                ScopeInterface::SCOPE_STORE
            ),
            'state' => $this->scopeConfig->getValue(
                $path . '/state',
                ScopeInterface::SCOPE_STORE
            ),
            'zip' => $this->scopeConfig->getValue(
                $path . '/zip',
                ScopeInterface::SCOPE_STORE
            ),
            'city' => $this->scopeConfig->getValue(
                $path . '/city',
                ScopeInterface::SCOPE_STORE
            )
        ];
    }
}