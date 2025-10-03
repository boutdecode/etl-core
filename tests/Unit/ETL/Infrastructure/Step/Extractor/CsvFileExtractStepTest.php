<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Infrastructure\Step\Extractor;

use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Extractor\CsvFileExtractStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CsvFileExtractStepTest extends TestCase
{
    private string $testCsvPath;

    private string $testCsvSemicolonPath;

    private CsvFileExtractStep $extractStep;

    protected function setUp(): void
    {
        $this->testCsvPath = __DIR__ . '/../../../../../fixtures/test_data.csv';
        $this->testCsvSemicolonPath = __DIR__ . '/../../../../../fixtures/test_data_semicolon.csv';
        $this->extractStep = new CsvFileExtractStep();
    }

    #[Test]
    public function getCodeShouldReturnCorrectCode(): void
    {
        $this->assertSame(CsvFileExtractStep::CODE, $this->extractStep->getCode());
        $this->assertSame('etl.extractor.csv_file', $this->extractStep->getCode());
    }

    #[Test]
    public function extractWithDefaultParametersShouldParseCSV(): void
    {
        $result = $this->extractStep->extract($this->testCsvPath);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $firstRow = $result[0];
        $this->assertArrayHasKey('name', $firstRow);
        $this->assertArrayHasKey('email', $firstRow);
        $this->assertArrayHasKey('age', $firstRow);

        $this->assertSame('John Doe', $firstRow['name']);
        $this->assertSame('john@example.com', $firstRow['email']);
        $this->assertSame('30', $firstRow['age']);
    }

    #[Test]
    public function extractWithArraySourceShouldWork(): void
    {
        $source = [
            'source' => $this->testCsvPath,
        ];

        $result = $this->extractStep->extract($source);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function extractWithCustomDelimiterShouldWork(): void
    {
        $configuration = [
            'delimiter' => ';',
        ];

        $result = $this->extractStep->extract($this->testCsvSemicolonPath, $configuration);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    #[Test]
    public function extractWithoutHeaderShouldCreateNumericKeys(): void
    {
        $configuration = [
            'hasHeader' => false,
        ];

        $result = $this->extractStep->extract($this->testCsvPath, $configuration);

        $this->assertIsArray($result);
        $firstRow = $result[0];

        // When no header, Flow-PHP creates entry_0, entry_1, etc. columns
        $keys = array_keys($firstRow);
        $this->assertNotEmpty($keys);
        $this->assertSame('name', $firstRow[$keys[0]]); // First row becomes data
    }

    #[Test]
    public function extractWithCustomEnclosureAndEscapeShouldWork(): void
    {
        $configuration = [
            'enclosure' => "'",
            'escape' => '/',
        ];

        $result = $this->extractStep->extract($this->testCsvPath, $configuration);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function extractShouldThrowExceptionForInvalidStringSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a string representing the file path.');

        $this->extractStep->extract(123);
    }

    #[Test]
    public function extractShouldThrowExceptionForNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found:');

        $this->extractStep->extract('/path/to/non/existent/file.csv');
    }

    #[Test]
    public function extractShouldThrowExceptionForArrayWithoutSourceKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a string representing the file path.');

        $this->extractStep->extract([
            'not_source' => 'value',
        ]);
    }

    #[Test]
    public function constructWithCustomParametersShouldSetDefaults(): void
    {
        $extractStep = new CsvFileExtractStep(';', false);

        $this->assertSame('etl.extractor.csv_file', $extractStep->getCode());

        // Test that custom defaults are used
        $result = $extractStep->extract($this->testCsvSemicolonPath);
        $this->assertIsArray($result);
    }

    #[Test]
    public function configurationParametersShouldOverrideConstructorDefaults(): void
    {
        $extractStep = new CsvFileExtractStep(';', false);

        $configuration = [
            'delimiter' => ',',
            'hasHeader' => true,
        ];
        $result = $extractStep->extract($this->testCsvPath, $configuration);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('name', $result[0]);
    }

    #[Test]
    public function extractWithNonBooleanHasHeaderShouldUseDefault(): void
    {
        $configuration = [
            'hasHeader' => 'not_a_boolean',
        ];

        $result = $this->extractStep->extract($this->testCsvPath, $configuration);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result[0]); // Should use default (true)
    }

    #[Test]
    public function extractWithNonStringDelimiterShouldUseDefault(): void
    {
        $configuration = [
            'delimiter' => 123,
        ];

        $result = $this->extractStep->extract($this->testCsvPath, $configuration);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function stepShouldImplementCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep::class,
            $this->extractStep
        );
    }
}
