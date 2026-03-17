<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExtractorStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractExtractorStepTest extends TestCase
{
    private TestExtractorStep $extractorStep;

    protected function setUp(): void
    {
        $this->extractorStep = new TestExtractorStep();
    }

    #[Test]
    public function getCodeShouldReturnSetCode(): void
    {
        $this->assertSame('test.extractor.code', $this->extractorStep->getCode());
    }

    #[Test]
    public function getNameShouldReturnNameWhenSet(): void
    {
        $this->assertSame('Test Extractor', $this->extractorStep->getName());
    }

    #[Test]
    public function getNameShouldReturnCodeWhenNameNotSet(): void
    {
        $extractorStep = new TestExtractorStepWithoutName();

        $this->assertSame('test.extractor.no.name', $extractorStep->getName());
    }

    #[Test]
    public function setNameShouldUpdateName(): void
    {
        $newName = 'Updated Extractor Name';
        $this->extractorStep->setName($newName);

        $this->assertSame($newName, $this->extractorStep->getName());
    }

    #[Test]
    public function getConfigurationShouldReturnSetConfiguration(): void
    {
        $expected = [
            'param1' => 'value1',
            'param2' => 'value2',
        ];
        $this->assertSame($expected, $this->extractorStep->getConfiguration());
    }

    #[Test]
    public function setConfigurationShouldUpdateConfiguration(): void
    {
        $newConfig = [
            'newParam' => 'newValue',
        ];
        $this->extractorStep->setConfiguration($newConfig);

        $this->assertSame($newConfig, $this->extractorStep->getConfiguration());
    }

    #[Test]
    public function processShouldExtractDataAndSetResult(): void
    {
        $inputData = 'test input data';
        $context = new Context($inputData);

        $result = $this->extractorStep->process($context);

        $this->assertSame($context, $result);
        $this->assertSame([
            'extracted' => 'test input data',
        ], $result->getResult());
    }

    #[Test]
    public function processShouldUseContextConfigurationWhenAvailable(): void
    {
        $inputData = 'test input';
        $context = new Context($inputData);
        $context->setConfigurationValue('Test Extractor', [
            'custom' => 'config',
        ]);

        $result = $this->extractorStep->process($context);

        $this->assertSame($context, $result);
        // The test step should have used the custom config
        $this->assertSame([
            'extracted with custom config' => 'test input',
        ], $result->getResult());
    }

    #[Test]
    public function processShouldUseEmptyConfigWhenContextConfigIsNotArray(): void
    {
        $inputData = 'test input';
        $context = new Context($inputData);
        $context->setConfigurationValue('Test Extractor', 'not an array');

        $result = $this->extractorStep->process($context);

        $this->assertSame($context, $result);
        $this->assertSame([
            'extracted' => 'test input',
        ], $result->getResult());
    }

    #[Test]
    public function processShouldHandleNullContextConfiguration(): void
    {
        $inputData = 'test input';
        $context = new Context($inputData);
        $context->setConfigurationValue('Test Extractor', null);

        $result = $this->extractorStep->process($context);

        $this->assertSame($context, $result);
        $this->assertSame([
            'extracted' => 'test input',
        ], $result->getResult());
    }

    #[Test]
    public function stepShouldImplementCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExtractorStep::class,
            $this->extractorStep
        );
    }

    #[Test]
    public function getOrderShouldReturnOrder(): void
    {
        $this->assertSame(0, $this->extractorStep->getOrder());
    }

    #[Test]
    public function setOrderShouldUpdateOrder(): void
    {
        $this->extractorStep->setOrder(10);

        $this->assertSame(10, $this->extractorStep->getOrder());
    }
}

// Test class for testing AbstractExtractorStep
class TestExtractorStep extends AbstractExtractorStep
{
    public function __construct()
    {
        $this->name = 'Test Extractor';
        $this->code = 'test.extractor.code';
        $this->configuration = [
            'param1' => 'value1',
            'param2' => 'value2',
        ];
    }

    public function extract(mixed $source, Context $context, array $configuration = []): array
    {
        if (isset($configuration['custom']) && $configuration['custom'] === 'config') {
            return [
                'extracted with custom config' => $source,
            ];
        }

        return [
            'extracted' => $source,
        ];
    }
}

// Test class without name set
class TestExtractorStepWithoutName extends AbstractExtractorStep
{
    public function __construct()
    {
        $this->code = 'test.extractor.no.name';
        $this->configuration = [];
    }

    public function extract(mixed $source, Context $context, array $configuration = []): array
    {
        return [
            'extracted' => $source,
        ];
    }
}
