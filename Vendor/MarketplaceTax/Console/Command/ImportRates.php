<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRates extends Command
{
    private const OPTION_FILE = 'file';
    private const OPTION_DRY_RUN = 'dry-run';

    private const REQUIRED_COLUMNS = [
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

    private const ALLOWED_LEVELS = [
        'STATE',
        'COUNTY',
        'CITY',
        'SPECIAL'
    ];

    private const VALID_US_STATES = [
        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE',
        'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS',
        'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS',
        'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY',
        'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
        'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV',
        'WI', 'WY', 'DC'
    ];

    private const BATCH_SIZE = 500;

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('marketplace-tax:rates:import')
            ->setDescription('Import marketplace tax rates')
            ->addOption(
                self::OPTION_FILE,
                null,
                InputOption::VALUE_REQUIRED,
                'CSV file path'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Validate without saving'
            );

        parent::configure();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $file = (string)$input->getOption(self::OPTION_FILE);
        $dryRun = (bool)$input->getOption(self::OPTION_DRY_RUN);

        if ($file === '' || !is_readable($file)) {
            $output->writeln(
                '<error>CSV file not found or not readable: '
                . $file . '</error>'
            );

            return Cli::RETURN_FAILURE;
        }

        $handle = fopen($file, 'rb');

        if ($handle === false) {
            $output->writeln(
                '<error>Unable to open CSV file.</error>'
            );

            return Cli::RETURN_FAILURE;
        }

        try {
            return $this->processImport(
                $handle,
                $dryRun,
                $output
            );
        } catch (\Throwable $exception) {
            $output->writeln(
                '<error>Import failed: '
                . $exception->getMessage()
                . '</error>'
            );

            return Cli::RETURN_FAILURE;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Process CSV import.
     *
     * @param resource $handle
     */
    private function processImport(
        $handle,
        bool $dryRun,
        OutputInterface $output
    ): int {
        // PHP 8.4 compatibility: explicitly specify escape.
        $header = fgetcsv($handle, 0, ',', '"', '\\');

        if ($header === false) {
            $output->writeln(
                '<error>CSV file is empty.</error>'
            );

            return Cli::RETURN_FAILURE;
        }

        // Remove UTF-8 BOM if present.
        if (isset($header[0])) {
            $header[0] = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $header[0]
            );
        }

        if ($header !== self::REQUIRED_COLUMNS) {
            $output->writeln(
                '<error>Invalid CSV header. Expected: '
                . implode(',', self::REQUIRED_COLUMNS)
                . '</error>'
            );

            return Cli::RETURN_FAILURE;
        }

        $errors = [];
        $rowNumber = 1;
        $rowCount = 0;

        // First pass: validate every row.
        while (
            ($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false
        ) {
            $rowNumber++;

            // Skip completely empty lines.
            if ($row === [null] || $row === []) {
                continue;
            }

            if (count($row) !== count($header)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'column' => 'CSV',
                    'message' => 'Incorrect column count'
                ];

                continue;
            }

            $data = array_combine($header, $row);

            if ($data === false) {
                $errors[] = [
                    'row' => $rowNumber,
                    'column' => 'CSV',
                    'message' => 'Unable to parse CSV row'
                ];

                continue;
            }

            $rowCount++;

            $errors = array_merge(
                $errors,
                $this->validateRow($data, $rowNumber)
            );
        }

        // No database changes if validation fails.
        if ($errors !== []) {
            $output->writeln('');
            $output->writeln(
                '<error>CSV validation failed.</error>'
            );

            foreach ($errors as $error) {
                $output->writeln(sprintf(
                    'Row %d | Column: %s | %s',
                    $error['row'],
                    $error['column'],
                    $error['message']
                ));
            }

            $output->writeln(
                sprintf('Total errors: %d', count($errors))
            );

            return Cli::RETURN_FAILURE;
        }

        if ($rowCount === 0) {
            $output->writeln(
                '<comment>No data rows found.</comment>'
            );

            return Cli::RETURN_SUCCESS;
        }

        $output->writeln(
            sprintf('Validated rows: %d', $rowCount)
        );

        if ($dryRun) {
            $output->writeln(
                '<info>Validation successful. Dry run completed. '
                . 'No database changes made.</info>'
            );

            return Cli::RETURN_SUCCESS;
        }

        // Second pass: perform database writes.
        rewind($handle);

        // Skip header.
        fgetcsv($handle, 0, ',', '"', '\\');

        $connection = $this->resource->getConnection();

        $table = $this->resource->getTableName(
            'marketplace_tax_rate'
        );

        $batch = [];
        $importedRows = 0;

        $connection->beginTransaction();

        try {
            while (
                ($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false
            ) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                $data = array_combine($header, $row);

                if ($data === false) {
                    throw new \RuntimeException(
                        'Unable to parse CSV during import.'
                    );
                }

                $batch[] = $this->prepareRow($data);

                if (count($batch) >= self::BATCH_SIZE) {
                    $this->saveBatch(
                        $connection,
                        $table,
                        $batch
                    );

                    $importedRows += count($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $this->saveBatch(
                    $connection,
                    $table,
                    $batch
                );

                $importedRows += count($batch);
            }

            $connection->commit();

        } catch (\Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        $output->writeln('');
        $output->writeln(
            '<info>Rates imported successfully.</info>'
        );

        $output->writeln(
            sprintf('Imported rows: %d', $importedRows)
        );

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Validate an individual CSV row.
     */
    private function validateRow(
        array $data,
        int $row
    ): array {
        $errors = [];

        $addError = static function (
            string $column,
            string $message
        ) use (&$errors, $row): void {
            $errors[] = [
                'row' => $row,
                'column' => $column,
                'message' => $message
            ];
        };

        // Required fields.
        foreach (
            ['rate_code', 'country', 'state', 'level', 'jurisdiction_name', 'rate', 'status']
            as $column
        ) {
            if (trim((string)$data[$column]) === '') {
                $addError(
                    $column,
                    'Field is required'
                );
            }
        }

        if ($data['rate_code'] === '') {
            return $errors;
        }

        // Country validation.
        if ($data['country'] !== 'US') {
            $addError(
                'country',
                'Only US rates are supported'
            );
        }

        // State validation.
        if (
            !in_array(
                strtoupper($data['state']),
                self::VALID_US_STATES,
                true
            )
        ) {
            $addError(
                'state',
                'Invalid US state code'
            );
        }

        // Jurisdiction level validation.
        if (
            !in_array(
                strtoupper($data['level']),
                self::ALLOWED_LEVELS,
                true
            )
        ) {
            $addError(
                'level',
                'Invalid jurisdiction level'
            );
        }

        // Rate validation.
        if (
            !is_numeric($data['rate'])
            || (float)$data['rate'] < 0
            || (float)$data['rate'] > 100
        ) {
            $addError(
                'rate',
                'Rate must be between 0 and 100'
            );
        }

        // ZIP validation.
        foreach (['zip_from', 'zip_to'] as $column) {
            if (
                $data[$column] !== ''
                && !preg_match('/^\d{5}$/', $data[$column])
            ) {
                $addError(
                    $column,
                    'ZIP must contain exactly 5 digits'
                );
            }
        }

        if (
            $data['zip_from'] !== ''
            && $data['zip_to'] !== ''
            && (int)$data['zip_from'] > (int)$data['zip_to']
        ) {
            $addError(
                'zip_from',
                'ZIP start cannot exceed ZIP end'
            );
        }

        // Validate dates.
        foreach (['valid_from', 'valid_to'] as $column) {
            if (
                $data[$column] !== ''
                && !$this->isValidDate($data[$column])
            ) {
                $addError(
                    $column,
                    'Invalid date. Expected YYYY-MM-DD'
                );
            }
        }

        if (
            $data['valid_from'] !== ''
            && $data['valid_to'] !== ''
            && $this->isValidDate($data['valid_from'])
            && $this->isValidDate($data['valid_to'])
            && $data['valid_from'] > $data['valid_to']
        ) {
            $addError(
                'valid_to',
                'End date cannot be before start date'
            );
        }

        // Status validation.
        if (
            !in_array(
                (string)$data['status'],
                ['0', '1'],
                true
            )
        ) {
            $addError(
                'status',
                'Status must be 0 or 1'
            );
        }

        return $errors;
    }

    /**
     * Prepare validated CSV row for database insertion.
     */
    private function prepareRow(array $data): array
    {
        return [
            'rate_code' => trim($data['rate_code']),
            'country' => strtoupper(trim($data['country'])),
            'state' => strtoupper(trim($data['state'])),

            'zip_from' => $data['zip_from'] !== ''
                ? (int)$data['zip_from']
                : null,

            'zip_to' => $data['zip_to'] !== ''
                ? (int)$data['zip_to']
                : null,

            'level' => strtoupper(trim($data['level'])),

            'jurisdiction_name' => trim(
                $data['jurisdiction_name']
            ),

            'rate' => $data['rate'],

            'valid_from' => $data['valid_from'] !== ''
                ? $data['valid_from']
                : null,

            'valid_to' => $data['valid_to'] !== ''
                ? $data['valid_to']
                : null,

            'status' => (int)$data['status']
        ];
    }

    /**
     * Save a batch using rate_code as the unique key.
     */
    private function saveBatch(
        $connection,
        string $table,
        array $batch
    ): void {
        $connection->insertOnDuplicate(
            $table,
            $batch,
            [
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
            ]
        );
    }

    /**
     * Validate YYYY-MM-DD date.
     */
    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date;
    }
}