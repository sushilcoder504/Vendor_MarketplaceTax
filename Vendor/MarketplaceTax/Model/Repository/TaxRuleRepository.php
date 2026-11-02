<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model\Repository;

use Magento\Framework\App\ResourceConnection;
use Vendor\MarketplaceTax\Api\TaxRuleRepositoryInterface;

class TaxRuleRepository implements TaxRuleRepositoryInterface
{
    private const TABLE = 'marketplace_tax_rule';

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function getApplicableRules(
        string $category,
        string $country,
        string $state,
        string $zip,
        string $date
    ): array {
        $connection = $this->resource->getConnection();

        $table = $this->resource->getTableName(self::TABLE);

        $zipNumber = (int)$zip;

        $select = $connection->select()
            ->from($table)
            ->where('status = ?', 1)
            ->where('tax_category IN (?)', [$category, '*'])
            ->where('(country IS NULL OR country = ?)', $country)
            ->where('(state IS NULL OR state = ?)', $state)
            ->where('(zip_from IS NULL OR zip_from <= ?)', $zipNumber)
            ->where('(zip_to IS NULL OR zip_to >= ?)', $zipNumber)
            ->where('(valid_from IS NULL OR valid_from <= ?)', $date)
            ->where('(valid_to IS NULL OR valid_to >= ?)', $date)
            ->order('priority DESC');

        return $connection->fetchAll($select);
    }

    public function save(array $data): int
    {
        $connection = $this->resource->getConnection();

        $table = $this->resource->getTableName(self::TABLE);

        $connection->insertOnDuplicate(
            $table,
            $data,
            array_keys($data)
        );

        return (int)$connection->lastInsertId($table);
    }
}