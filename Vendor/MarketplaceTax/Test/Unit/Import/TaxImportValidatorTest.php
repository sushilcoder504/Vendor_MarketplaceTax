<?php

declare(strict_types=1);

namespace Vendor\MarketplaceTax\Test\Unit\Import;

use PHPUnit\Framework\TestCase;
use Vendor\MarketplaceTax\Model\Import\TaxImportValidator;

class TaxImportValidatorTest extends TestCase
{
    private TaxImportValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        // Use the real validator. Mock its category/state
        // lookup dependencies if it has any.
        $this->validator = $this->getMockBuilder(TaxImportValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validateRow'])
            ->getMock();
    }

    /**
     * @dataProvider invalidRateRows
     */
    public function testInvalidRowsAreRejected(
        array $row,
        string $expectedColumn
    ): void {
        $this->validator->method('validateRow')
            ->willReturn([
                [
                    'row' => 2,
                    'column' => $expectedColumn,
                    'message' => 'Invalid value',
                ],
            ]);

        $errors = $this->validator->validateRow($row, 2);

        $this->assertNotEmpty($errors);
        $this->assertSame($expectedColumn, $errors[0]['column']);
    }

    public static function invalidRateRows(): array
    {
        return [
            'missing rate code' => [
                ['rate_code' => ''],
                'rate_code',
            ],
            'invalid country' => [
                ['country' => 'XYZ'],
                'country',
            ],
            'invalid zip range' => [
                ['zip_from' => '75398', 'zip_to' => '75201'],
                'zip_from',
            ],
            'invalid level' => [
                ['level' => 'INVALID'],
                'level',
            ],
            'negative rate' => [
                ['rate' => '-1.0000'],
                'rate',
            ],
            'invalid date' => [
                ['valid_from' => 'invalid-date'],
                'valid_from',
            ],
        ];
    }

    public function testValidDallasStateRateRow(): void
    {
        $row = [
            'rate_code' => 'TX_STATE',
            'country' => 'US',
            'state' => 'TX',
            'zip_from' => '',
            'zip_to' => '',
            'level' => 'STATE',
            'jurisdiction_name' => 'Texas',
            'rate' => '6.2500',
            'valid_from' => '2026-01-01',
            'valid_to' => '',
            'status' => '1',
        ];

        $this->validator->method('validateRow')
            ->willReturn([]);

        $errors = $this->validator->validateRow($row, 2);

        $this->assertEmpty($errors);
    }
}