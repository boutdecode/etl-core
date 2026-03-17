<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractTransformerStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractTransformerStepTest extends TestCase
{
    private ConcreteTransformerStep $transformerStep;

    protected function setUp(): void
    {
        $this->transformerStep = new ConcreteTransformerStep();
    }

    #[Test]
    public function itGetsCode(): void
    {
        $this->assertSame('test_transformer', $this->transformerStep->getCode());
    }

    #[Test]
    public function itGetsNameWhenSet(): void
    {
        $this->transformerStep->setName('Custom Name');
        $this->assertSame('Custom Name', $this->transformerStep->getName());
    }

    #[Test]
    public function itGetsCodeAsNameWhenNameNotSet(): void
    {
        $this->assertSame('test_transformer', $this->transformerStep->getName());
    }

    #[Test]
    public function itSetsAndGetsConfiguration(): void
    {
        $config = [
            'key' => 'value',
            'timeout' => 30,
        ];

        $this->transformerStep->setConfiguration($config);

        $this->assertSame($config, $this->transformerStep->getConfiguration());
    }

    #[Test]
    public function itStartsWithEmptyConfiguration(): void
    {
        $this->assertSame([], $this->transformerStep->getConfiguration());
    }

    #[Test]
    public function itProcessesContextWithStepConfiguration(): void
    {
        $input = [
            'field1' => 'value1',
            'field2' => 'value2',
        ];
        $stepConfig = [
            'step_param' => 'step_value',
        ];
        $context = new Context(
            $input,
            [], // resultSet
            [], // inputSet
            [
                'test_transformer' => $stepConfig,
            ] // configuration
        );

        $result = $this->transformerStep->process($context);

        $this->assertInstanceOf(Context::class, $result);
        $this->assertSame([
            'transformed_data' => $input,
            'config_used' => $stepConfig,
        ], $result->getResultByKey('test_transformer'));
    }

    #[Test]
    public function itProcessesContextWithEmptyConfigurationWhenNoStepConfig(): void
    {
        $input = [
            'field1' => 'value1',
        ];
        $context = new Context($input);

        $result = $this->transformerStep->process($context);

        $this->assertSame([
            'transformed_data' => $input,
            'config_used' => [],
        ], $result->getResultByKey('test_transformer'));
    }

    #[Test]
    public function itProcessesContextWithEmptyConfigurationWhenStepConfigNotArray(): void
    {
        $input = [
            'field1' => 'value1',
        ];
        $context = new Context(
            $input,
            [], // resultSet
            [], // inputSet
            [
                'test_transformer' => 'not_an_array',
            ] // configuration
        );

        $result = $this->transformerStep->process($context);

        $this->assertSame([
            'transformed_data' => $input,
            'config_used' => [],
        ], $result->getResultByKey('test_transformer'));
    }

    #[Test]
    public function itProcessesContextWithCustomName(): void
    {
        $this->transformerStep->setName('custom_transformer');
        $input = [
            'data' => 'test',
        ];
        $stepConfig = [
            'custom_param' => 'custom_value',
        ];
        $context = new Context(
            $input,
            [], // resultSet
            [], // inputSet
            [
                'custom_transformer' => $stepConfig,
            ] // configuration
        );

        $result = $this->transformerStep->process($context);

        $this->assertSame([
            'transformed_data' => $input,
            'config_used' => $stepConfig,
        ], $result->getResultByKey('custom_transformer')); // Changed from 'test_transformer' to 'custom_transformer'
    }

    #[Test]
    public function itHandlesComplexInputData(): void
    {
        $input = [
            'users' => [
                [
                    'id' => 1,
                    'name' => 'John',
                ],
                [
                    'id' => 2,
                    'name' => 'Jane',
                ],
            ],
            'metadata' => [
                'count' => 2,
            ],
        ];
        $context = new Context($input);

        $result = $this->transformerStep->process($context);

        $this->assertSame([
            'transformed_data' => $input,
            'config_used' => [],
        ], $result->getResultByKey('test_transformer'));
    }

    #[Test]
    public function itPreservesOriginalContextData(): void
    {
        $originalInput = [
            'original' => 'data',
        ];
        $originalConfig = [
            'other_step' => [
                'param' => 'value',
            ],
        ];
        $context = new Context(
            $originalInput,
            [], // resultSet
            [], // inputSet
            $originalConfig // configuration
        );

        $result = $this->transformerStep->process($context);

        // The input gets modified by setResult(), but configuration should remain accessible
        $this->assertSame([
            'param' => 'value',
        ], $result->getConfigurationValue('other_step'));
        // Verify that the step's result is properly stored
        $this->assertSame([
            'transformed_data' => $originalInput,
            'config_used' => [],
        ], $result->getResultByKey('test_transformer'));
    }

    #[Test]
    public function itGetsOrder(): void
    {
        $this->assertSame(0, $this->transformerStep->getOrder());
    }

    #[Test]
    public function itSetsOrder(): void
    {
        $this->transformerStep->setOrder(5);

        $this->assertSame(5, $this->transformerStep->getOrder());
    }
}

/**
 * Concrete implementation of AbstractTransformerStep for testing purposes
 */
class ConcreteTransformerStep extends AbstractTransformerStep
{
    protected string $code = 'test_transformer';

    public function transform(mixed $data, Context $context, array $configuration = []): mixed
    {
        return [
            'transformed_data' => $data,
            'config_used' => $configuration,
        ];
    }
}
