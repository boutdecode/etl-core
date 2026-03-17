<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Infrastructure\Step\Extractor;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Extractor\XmlFileExtractStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

class XmlFileExtractStepTest extends TestCase
{
    private string $testXmlPath;

    private string $testNestedXmlPath;

    private XmlFileExtractStep $extractStep;

    private Context $context;

    protected function setUp(): void
    {
        $this->testXmlPath = __DIR__ . '/../../../../../fixtures/test_data.xml';
        $this->testNestedXmlPath = __DIR__ . '/../../../../../fixtures/test_nested.xml';
        $this->extractStep = new XmlFileExtractStep();
        $this->context = new Context(null);
    }

    #[Test]
    public function getCodeShouldReturnCorrectCode(): void
    {
        $this->assertSame('etl.extractor.xml_file', $this->extractStep->getCode());
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
        $this->assertArrayHasKey('rootNode', $description);
        $this->assertArrayHasKey('recordNode', $description);
        $this->assertArrayHasKey('useAttributes', $description);
    }

    #[Test]
    public function extractWithDefaultParametersShouldParseRecordNodes(): void
    {
        $result = $this->extractStep->extract($this->testXmlPath, $this->context);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $firstRecord = $result[0];
        $this->assertArrayHasKey('name', $firstRecord);
        $this->assertArrayHasKey('email', $firstRecord);
        $this->assertArrayHasKey('age', $firstRecord);
        $this->assertSame('John Doe', $firstRecord['name']);
        $this->assertSame('john@example.com', $firstRecord['email']);
        $this->assertSame('30', $firstRecord['age']);
    }

    #[Test]
    public function extractWithArraySourceShouldWork(): void
    {
        $source = [
            'source' => $this->testXmlPath,
        ];

        $result = $this->extractStep->extract($source, $this->context);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function extractWithAttributesShouldIncludeThemWhenUseAttributesIsTrue(): void
    {
        $result = $this->extractStep->extract($this->testXmlPath, $this->context);

        $firstRecord = $result[0];
        $this->assertArrayHasKey('@id', $firstRecord);
        $this->assertSame('1', $firstRecord['@id']);
    }

    #[Test]
    public function extractWithUseAttributesFalseShouldExcludeAttributes(): void
    {
        $extractStep = new XmlFileExtractStep(useAttributes: false);

        $result = $extractStep->extract($this->testXmlPath, $this->context);

        $this->assertIsArray($result);
        $firstRecord = $result[0];
        $this->assertArrayNotHasKey('@id', $firstRecord);
        $this->assertArrayHasKey('name', $firstRecord);
    }

    #[Test]
    public function extractWithConfigurationUseAttributesFalseShouldOverrideConstructorDefault(): void
    {
        $result = $this->extractStep->extract($this->testXmlPath, $this->context, [
            'useAttributes' => false,
        ]);

        $firstRecord = $result[0];
        $this->assertArrayNotHasKey('@id', $firstRecord);
    }

    #[Test]
    public function extractWithCustomRecordNodeShouldUseIt(): void
    {
        $extractStep = new XmlFileExtractStep(recordNode: 'item');

        $result = $extractStep->extract($this->testNestedXmlPath, $this->context);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('title', $result[0]);
        $this->assertSame('Widget A', $result[0]['title']);
    }

    #[Test]
    public function extractWithRecordNodeInConfigurationShouldOverrideConstructorDefault(): void
    {
        $result = $this->extractStep->extract($this->testNestedXmlPath, $this->context, [
            'recordNode' => 'item',
        ]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    #[Test]
    public function extractWithNonExistentRecordNodeShouldReturnWholeDocument(): void
    {
        $result = $this->extractStep->extract($this->testXmlPath, $this->context, [
            'recordNode' => 'nonexistent',
        ]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    #[Test]
    public function extractShouldThrowExceptionForNonStringSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a string representing the file path.');

        $this->extractStep->extract(42, $this->context);
    }

    #[Test]
    public function extractShouldThrowExceptionForArraySourceWithoutSourceKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be a string representing the file path.');

        $this->extractStep->extract([
            'not_source' => 'value',
        ], $this->context);
    }

    #[Test]
    public function extractShouldThrowExceptionForNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found:');

        $this->extractStep->extract('/path/to/nonexistent.xml', $this->context);
    }

    #[Test]
    #[WithoutErrorHandler]
    public function extractShouldThrowExceptionForInvalidXmlContent(): void
    {
        $invalidXmlPath = sys_get_temp_dir() . '/invalid_test.xml';
        file_put_contents($invalidXmlPath, 'this is not xml <<>>');

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid XML format in file:');

            libxml_use_internal_errors(true);
            $this->extractStep->extract($invalidXmlPath, $this->context);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            @unlink($invalidXmlPath);
        }
    }

    #[Test]
    public function extractWithNonBoolUseAttributesShouldFallBackToConstructorDefault(): void
    {
        $result = $this->extractStep->extract($this->testXmlPath, $this->context, [
            'useAttributes' => 'not_a_bool',
        ]);

        $firstRecord = $result[0];
        // Default useAttributes is true → attributes should be present
        $this->assertArrayHasKey('@id', $firstRecord);
    }

    #[Test]
    public function extractWithNonStringRecordNodeShouldFallBackToConstructorDefault(): void
    {
        $result = $this->extractStep->extract($this->testXmlPath, $this->context, [
            'recordNode' => 123,
        ]);

        // Default recordNode is 'record' → the 3 records should be extracted
        $this->assertCount(3, $result);
    }
}
