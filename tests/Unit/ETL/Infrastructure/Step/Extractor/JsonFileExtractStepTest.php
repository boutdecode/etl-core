<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Infrastructure\Step\Extractor;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Extractor\JsonFileExtractStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class JsonFileExtractStepTest extends TestCase
{
    private string $testJsonPath;

    private JsonFileExtractStep $extractStep;

    private Context $context;

    protected function setUp(): void
    {
        $this->testJsonPath = __DIR__ . '/../../../../../fixtures/test_data.json';
        $this->extractStep = new JsonFileExtractStep();
        $this->context = new Context(null);
    }

    #[Test]
    public function getCodeShouldReturnCorrectCode(): void
    {
        $this->assertSame('etl.extractor.json_file', $this->extractStep->getCode());
    }

    #[Test]
    public function stepShouldExtendAbstractExtractorStep(): void
    {
        $this->assertInstanceOf(AbstractExtractorStep::class, $this->extractStep);
    }

    #[Test]
    public function getConfigurationDescriptionShouldReturnExpectedKeys(): void
    {
        $description = $this->extractStep->getConfigurationDescription();

        $this->assertIsArray($description);
        $this->assertArrayHasKey('source', $description);
        $this->assertArrayHasKey('pointer', $description);
    }

    #[Test]
    public function extractWithDefaultParametersShouldParseJson(): void
    {
        $result = $this->extractStep->extract($this->testJsonPath, $this->context);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $firstRow = $result[0];
        $this->assertArrayHasKey('name', $firstRow);
        $this->assertArrayHasKey('email', $firstRow);
        $this->assertArrayHasKey('age', $firstRow);

        $this->assertSame('John Doe', $firstRow['name']);
        $this->assertSame('john@example.com', $firstRow['email']);
    }

    #[Test]
    public function extractWithArraySourceShouldWork(): void
    {
        $source = [
            'source' => $this->testJsonPath,
        ];

        $result = $this->extractStep->extract($source, $this->context);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function extractWithArraySourceAndConfigurationShouldWork(): void
    {
        $source = [];

        $result = $this->extractStep->extract($source, $this->context, [
            'source' => $this->testJsonPath,
        ]);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function extractShouldThrowExceptionForInvalidSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a string representing the file path.');

        $this->extractStep->extract(123, $this->context);
    }

    #[Test]
    public function extractShouldThrowExceptionForNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found:');

        $this->extractStep->extract('/path/to/non/existent/file.json', $this->context);
    }

    #[Test]
    public function extractShouldThrowExceptionForArrayWithoutSourceKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a string representing the file path.');

        $this->extractStep->extract([
            'not_source' => 'value',
        ], $this->context);
    }

    #[Test]
    public function constructWithCustomPointerShouldSetDefault(): void
    {
        $extractStep = new JsonFileExtractStep('/users');

        $this->assertSame('etl.extractor.json_file', $extractStep->getCode());
    }

    #[Test]
    public function extractWithNonStringPointerInConfigurationShouldIgnoreIt(): void
    {
        $result = $this->extractStep->extract($this->testJsonPath, $this->context, [
            'pointer' => 123,
        ]);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function constructWithNullPointerShouldReadWholeFile(): void
    {
        $extractStep = new JsonFileExtractStep(null);

        $result = $extractStep->extract($this->testJsonPath, $this->context);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }
}
