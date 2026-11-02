<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model\Total;

use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Vendor\MarketplaceTax\Api\TaxCalculatorInterface;
use Vendor\MarketplaceTax\Model\Resolver\PlaceOfSupplyResolver;
use Vendor\MarketplaceTax\Model\Config;

class MarketplaceTax extends AbstractTotal
{
    public function __construct(
        private readonly TaxCalculatorInterface $calculator,
        private readonly PlaceOfSupplyResolver $placeOfSupplyResolver,
        private readonly Config $config,
        private readonly TimezoneInterface $timezone
    ) {
        $this->setCode('marketplace_tax');
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $total->setTotalAmount($this->getCode(), 0);
        $total->setBaseTotalAmount($this->getCode(), 0);

        $address = $shippingAssignment->getShipping()->getAddress();

        $destination = [
            'country' => $address->getCountryId(),
            'state' => $address->getRegionCode(),
            'zip' => $address->getPostcode(),
            'city' => $address->getCity()
        ];

        $shippingMethod = $address->getShippingMethod() ?? '';

        $origin = $this->config->getWarehouseAddress();

        $placeOfSupply = $this->placeOfSupplyResolver->resolve([
            'shipping_method' => $shippingMethod,
            'destination' => $destination,
            'origin' => $origin
        ]);

        $orderDate = $this->timezone->date()->format('Y-m-d');

        $grandTax = 0.0;

        foreach ($shippingAssignment->getItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $product = $item->getProduct();

            $category = (string)$product->getData('tax_category');

            $quantity = (float)$item->getQty();

            $discount = (float)$item->getDiscountAmount();

            $rowTotal = (float)$item->getRowTotal();

            $unitPrice = $quantity > 0
                ? ($rowTotal - $discount) / $quantity
                : 0.0;

            $calculation = $this->calculator->calculate([
                'tax_category' => $category,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'address' => $placeOfSupply,
                'date' => $orderDate
            ]);

            $itemTax = $calculation['tax_amount'];

            $item->setData('marketplace_tax_amount', $itemTax);

            $item->setData(
                'marketplace_tax_details',
                json_encode($calculation, JSON_THROW_ON_ERROR)
            );

            $grandTax += $itemTax;
        }

        // Shipping tax is calculated separately.
        if (
            $shippingMethod !== 'store_pickup'
            && $shippingMethod !== 'international'
            && $this->config->isShippingTaxable(
                $placeOfSupply['state'] ?? ''
            )
        ) {
            $shippingAmount = (float)$address->getShippingAmount();

            if ($shippingAmount > 0) {
                $shippingCalculation = $this->calculator->calculate([
                    'tax_category' => $this->config->getDefaultCategory(),
                    'unit_price' => $shippingAmount,
                    'quantity' => 1,
                    'address' => $placeOfSupply,
                    'date' => $orderDate
                ]);

                $shippingTax = $shippingCalculation['tax_amount'];

                $grandTax += $shippingTax;

                $address->setData(
                    'marketplace_shipping_tax_details',
                    json_encode(
                        $shippingCalculation,
                        JSON_THROW_ON_ERROR
                    )
                );
            }
        }

        $total->addTotalAmount($this->getCode(), $grandTax);
        $total->addBaseTotalAmount($this->getCode(), $grandTax);

        $total->setData('marketplace_tax_amount', $grandTax);

        return $this;
    }

    public function fetch(Quote $quote, Total $total): array
    {
        $amount = (float)$total->getData(
            'marketplace_tax_amount'
        );

        return [
            'code' => $this->getCode(),
            'title' => __('Sales Tax'),
            'value' => $amount
        ];
    }
}