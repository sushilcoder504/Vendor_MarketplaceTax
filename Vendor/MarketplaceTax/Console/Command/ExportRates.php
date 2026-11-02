<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportRates extends Command
{
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('marketplace-tax:rates:export')
            ->setDescription('Export marketplace tax rates')
            ->addOption('file', null, InputOption::VALUE_REQUIRED)
            ->addOption('state', null, InputOption::VALUE_OPTIONAL);

        parent::configure();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $file = $input->getOption('file');

        if (!$file) {
            $output->writeln('<error>Provide output file.</error>');
            return Cli::RETURN_FAILURE;
        }

        $connection = $this->resource->getConnection();

        $table = $this->resource->getTableName(
            'marketplace_tax_rate'
        );

        $select = $connection->select()->from($table);

        if ($state = $input->getOption('state')) {
            $select->where('state = ?', strtoupper($state));
        }

        $handle = fopen($file, 'wb');

        if (!$handle) {
            return Cli::RETURN_FAILURE;
        }

        $columns = [
            'rate_code',
            'country',
            'state',
            'zip_from',
            'zip_to',
            'level',
            'jurisdiction_name',
            'rate',
            'valid_from',
            'valid_to',
            'status'
        ];

        try {
            fputcsv($handle, $columns);

            // Iterate database results instead of loading all rows.
            $statement = $connection->query($select);

            while ($row = $statement->fetch()) {
                fputcsv($handle, [
                    $row['rate_code'],
                    $row['country'],
                    $row['state'],
                    $row['zip_from'],
                    $row['zip_to'],
                    $row['level'],
                    $row['jurisdiction_name'],
                    $row['rate'],
                    $row['valid_from'],
                    $row['valid_to'],
                    $row['status']
                ]);
            }
        } finally {
            fclose($handle);
        }

        $output->writeln('<info>Export completed.</info>');

        return Cli::RETURN_SUCCESS;
    }
}