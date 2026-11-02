<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model\Resolver;

use Vendor\MarketplaceTax\Api\TaxRuleRepositoryInterface;

class TaxabilityResolver
{
    public function __construct(
        private readonly TaxRuleRepositoryInterface $ruleRepository
    ) {
    }

    public function resolve(
        string $category,
        array $address,
        float $unitPrice,
        string $date
    ): array {
        $rules = $this->ruleRepository->getApplicableRules(
            $category,
            $address['country'],
            $address['state'] ?? '',
            $address['zip'] ?? '',
            $date
        );

        $applicable = [];

        foreach ($rules as $rule) {
            $minimum = $rule['min_unit_price'] !== null
                ? (float)$rule['min_unit_price'] : null;

            $maximum = $rule['max_unit_price'] !== null
                ? (float)$rule['max_unit_price'] : null;

            if ($minimum !== null && $unitPrice < $minimum) {
                continue;
            }

            if ($maximum !== null && $unitPrice > $maximum) {
                continue;
            }

            $specificity = 0;

            if ($rule['country'] !== null) {
                $specificity += 1;
            }

            if ($rule['state'] !== null) {
                $specificity += 1;
            }

            if (
                $rule['zip_from'] !== null
                && $rule['zip_to'] !== null
            ) {
                $specificity += 1;
            }

            $rule['specificity'] = $specificity;

            $applicable[] = $rule;
        }

        if (!$applicable) {
            return [
                'treatment' => 'TAXABLE',
                'rule_code' => null,
                'reason' => 'DEFAULT_TAXABLE'
            ];
        }

        usort($applicable, static function (
            array $a,
            array $b
        ): int {
            return [
                $b['specificity'],
                (int)$b['priority']
            ] <=> [
                $a['specificity'],
                (int)$a['priority']
            ];
        });

        $selected = $applicable[0];

        return [
            'treatment' => $selected['treatment'],
            'rule_code' => $selected['rule_code'],
            'reason' => $selected['treatment']
        ];
    }
}