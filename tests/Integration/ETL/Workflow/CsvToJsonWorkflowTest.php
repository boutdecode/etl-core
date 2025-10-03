<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Integration\ETL\Workflow;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Extractor\CsvFileExtractStep;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Loader\JsonFileLoadStep;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Transformer\DataMappingTransformStep;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Transformer\FilterTransformStep;
use PHPUnit\Framework\TestCase;

class CsvToJsonWorkflowTest extends TestCase
{
    private string $testDataDir;

    private string $outputDir;

    protected function setUp(): void
    {
        $this->testDataDir = dirname(__DIR__, 3) . '/fixtures/etl';
        $this->outputDir = dirname(__DIR__, 3) . '/fixtures/output';

        // Create directories if they don't exist
        if (! is_dir($this->testDataDir)) {
            mkdir($this->testDataDir, 0777, true);
        }
        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        // Create test CSV file
        $this->createTestCsvFile();
    }

    protected function tearDown(): void
    {
        // Clean up output files
        $outputFile = $this->outputDir . '/processed_data.json';
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
    }

    public function testCompleteETLWorkflow(): void
    {
        // Step 1: Extract from CSV
        $csvExtractor = new CsvFileExtractStep();
        $csvExtractor->setName('extract_customer_data');
        $csvExtractor->setConfiguration([
            'delimiter' => ',',
            'hasHeader' => true,
        ]);

        $context = new Context($this->testDataDir . '/customers.csv');
        $context = $csvExtractor->process($context);

        // Verify extraction
        $extractedData = $context->getResultByKey('extract_customer_data');
        $this->assertIsArray($extractedData);
        $this->assertNotEmpty($extractedData);
        $this->assertCount(3, $extractedData); // Should have 3 customer records

        // Verify extracted structure
        $firstRecord = $extractedData[0];
        $this->assertArrayHasKey('name', $firstRecord);
        $this->assertArrayHasKey('email', $firstRecord);
        $this->assertArrayHasKey('age', $firstRecord);
        $this->assertArrayHasKey('city', $firstRecord);

        // Step 2: Transform - Filter out customers under 25
        $filterTransformer = new FilterTransformStep();
        $filterTransformer->setName('filter_adult_customers');
        $filterTransformer->setConfiguration([
            'filterExpression' => 'item.age >= 25',
        ]);

        $context = new Context($extractedData);
        $context = $filterTransformer->process($context);

        $filteredData = $context->getResultByKey('filter_adult_customers');
        $this->assertIsArray($filteredData);
        $this->assertCount(2, $filteredData); // Should filter out one record

        // Step 3: Transform - Map fields
        $mappingTransformer = new DataMappingTransformStep();
        $mappingTransformer->setName('map_customer_fields');
        $mappingTransformer->setConfiguration([
            'fieldMapping' => [
                'name' => 'customer_name',
                'email' => 'email_address',
                'age' => [
                    'target' => 'customer_age',
                    'transform' => 'int',
                ],
                'city' => [
                    'target' => 'location',
                    'transform' => 'upper',
                ],
            ],
            'removeUnmappedFields' => true,
        ]);

        $context = new Context($filteredData);
        $context = $mappingTransformer->process($context);

        $mappedData = $context->getResultByKey('map_customer_fields');
        $this->assertNotNull($mappedData);
        $this->assertIsArray($mappedData);
        $this->assertCount(2, $mappedData);

        // Verify field mapping worked correctly
        $this->assertNotEmpty($mappedData);

        $firstMappedRecord = $mappedData[0];
        $this->assertIsArray($firstMappedRecord);
        $this->assertArrayHasKey('customer_name', $firstMappedRecord);
        $this->assertArrayHasKey('email_address', $firstMappedRecord);
        $this->assertArrayHasKey('customer_age', $firstMappedRecord);
        $this->assertArrayHasKey('location', $firstMappedRecord);

        // Verify transformations
        $this->assertIsInt($firstMappedRecord['customer_age']);
        $this->assertEquals('NEW YORK', $firstMappedRecord['location']);

        // Step 4: Load to JSON
        $jsonLoader = new JsonFileLoadStep();
        $jsonLoader->setName('save_to_json');

        $outputFile = $this->outputDir . '/processed_data.json';
        $loadResult = $jsonLoader->load($mappedData, $outputFile);

        // Verify loading
        $this->assertTrue($loadResult);
        $this->assertFileExists($outputFile);

        // Verify output file content
        $outputContent = file_get_contents($outputFile);
        $this->assertNotFalse($outputContent);

        $outputData = json_decode($outputContent, true);
        $this->assertIsArray($outputData);
        $this->assertCount(2, $outputData);

        // Verify final data structure
        $finalRecord = $outputData[0];
        $this->assertEquals('Alice Johnson', $finalRecord['customer_name']);
        $this->assertEquals('alice@example.com', $finalRecord['email_address']);
        $this->assertEquals(30, $finalRecord['customer_age']);
        $this->assertEquals('NEW YORK', $finalRecord['location']);
    }

    public function testWorkflowWithEmptyData(): void
    {
        // Create empty CSV file
        $emptyFile = $this->testDataDir . '/empty.csv';
        file_put_contents($emptyFile, "name,email,age,city\n");

        $csvExtractor = new CsvFileExtractStep();
        $context = new Context($emptyFile);
        $context = $csvExtractor->process($context);

        $extractedData = $context->getResultByKey('etl.extractor.csv_file');
        $this->assertIsArray($extractedData);
        $this->assertEmpty($extractedData);

        // Clean up
        unlink($emptyFile);
    }

    public function testWorkflowErrorHandling(): void
    {
        $csvExtractor = new CsvFileExtractStep();
        $context = new Context('/nonexistent/file.csv');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found: /nonexistent/file.csv');

        $csvExtractor->process($context);
    }

    private function createTestCsvFile(): void
    {
        $csvContent = "name,email,age,city\n";
        $csvContent .= "Alice Johnson,alice@example.com,30,New York\n";
        $csvContent .= "Bob Smith,bob@example.com,24,Los Angeles\n";
        $csvContent .= "Charlie Brown,charlie@example.com,35,Chicago\n";

        $csvFile = $this->testDataDir . '/customers.csv';
        file_put_contents($csvFile, $csvContent);
    }
}
