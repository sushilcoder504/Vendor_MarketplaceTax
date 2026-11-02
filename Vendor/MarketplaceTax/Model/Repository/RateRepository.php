<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Model\Repository;

use Magento\Framework\App\ResourceConnection;
use Vendor\MarketplaceTax\Api\RateRepositoryInterface;

class RateRepository implements RateRepositoryInterface
{
    private const TABLE = 'marketplace_tax_rate';

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function getByLocation(
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
            ->where('country = ?', $country)
            ->where('state = ?', $state)
            ->where('status = ?', 1)
            ->where('(zip_from IS NULL OR zip_from <= ?)', $zipNumber)
            ->where('(zip_to IS NULL OR zip_to >= ?)', $zipNumber)
            ->where('(valid_from IS NULL OR valid_from <= ?)', $date)
            ->where('(valid_to IS NULL OR valid_to >= ?)', $date);

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