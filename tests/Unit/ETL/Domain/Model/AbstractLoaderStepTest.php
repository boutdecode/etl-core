<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractLoaderStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractLoaderStepTest extends TestCase
{
    private ConcreteLoaderStep $loaderStep;

    protected function setUp(): void
    {
        $this->loaderStep = new ConcreteLoaderStep();
    }

    #[Test]
    public function itGetsCode(): void
    {
        $this->assertSame('test_loader', $this->loaderStep->getCode());
    }

    #[Test]
    public function itGetsNameWhenSet(): void
    {
        $this->loaderStep->setName('Custom Loader');
        $this->assertSame('Custom Loader', $this->loaderStep->getName());
    }

    #[Test]
    public function itGetsCodeAsNameWhenNameNotSet(): void
    {
        $this->assertSame('test_loader', $this->loaderStep->getName());
    }

    #[Test]
    public function itSetsAndGetsConfiguration(): void
    {
        $config = [
            'destination' => '/path/to/output',
            'format' => 'json',
        ];

        $this->loaderStep->setConfiguration($config);

        $this->assertSame($config, $this->loaderStep->getConfiguration());
    }

    #[Test]
    public function itStartsWithEmptyConfiguration(): void
    {
        $this->assertSame([], $this->loaderStep->getConfiguration());
    }

    #[Test]
    public function itProcessesContextWithDestinationFromStepConfig(): void
    {
        $input = [
            'data' => 'test_value',
        ];
        $stepConfig = [
            'destination' => '/tmp/output.json',
            'format' => 'json',
        ];
        $context = new Context(
            $input,
            [], // resultSet
            [], // inputSet
            [
                'test_loader' => $stepConfig,
            ] // configuration
        );

        $result = $this->loaderStep->process($context);

        $this->assertInstanceOf(Context::class, $result);
        $this->assertSame([
            'loaded_data' => $input,
            'destination' => '/tmp/output.json',
            'config_used' => $stepConfig,
        ], $result->getResultByKey('test_loader'));
    }

    #[Test]
    public function itProcessesContextWithDestinationFromDefaultConfig(): void
    {
        $this->loaderStep->setConfiguration([
            'destination' => '/default/path',
        ]);
        $input = [
            'field1' => 'value1',
        ];
        $context = new Context($input);

        $result = $this->loaderStep->process($context);

        $this->assertSame([
            'loaded_data' => $input,
            'destination' => '/default/path',
            'config_used' => [],  // Empty because no step config provided in context
        ], $result->getResultByKey('test_loader'));
    }

    #[Test]
    public function itProcessesContextWithNullDestinationWhenNoConfig(): void
    {
        $input = [
            'field1' => 'value1',
        ];
        $context = new Context($input);

        $result = $this->loaderStep->process($context);

        $this->assertSame([
            'loaded_data' => $input,
            'destination' => null,
            'config_used' => [],
        ], $result->getResultByKey('test_loader'));
    }

    #[Test]
    public function itPrioritizesStepConfigOverDefaultDestination(): void
    {
        $this->loaderStep->setConfiguration([
            'destination' => '/default/path',
        ]);
        $input = [
            'data' => 'test',
        ];
        $stepConfig = [
            'destination' => '/override/path',
        ];
        $context = new Context(
            $input,
            [], // resultSet
            [], // inputSet
            [
                'test_loader' => $stepConfig,
            ] // configuration
        );

        $result = $this->loaderStep->process($context);

        $this->assertSame([
            'loaded_data' => $input,
            'destination' => '/override/path',
            'config_used' => $stepConfig,
        ], $result->getResultByKey('test_loader'));
    }

    #[Test]
    public function itProcessesContextWithEmptyConfigWhenStepConfigNotArray(): void
    {
        $input = [
            'field1' => 'value1',
        ];
        $context = new Context(
            $input,
            [], // resultSet
            [], // inputSet
            [
                'test_loader' => 'not_an_array',
            ] // configuration
        );

        $result = $this->loaderStep->process($context);

        $this->assertSame([
            'loaded_data' => $input,
            'destination' => null,
            'config_used' => [],
        ], $result->getResultByKey('test_loader'));
    }

    #[Test]
    public function itProcessesContextWithCustomName(): void
    {
        $this->loaderStep->setName('custom_loader');
        $input = [
            'records' => [[
                'id' => 1,
            ]],
        ];
        $stepConfig = [
            'destination' => '/custom/path',
            'options' => [
                'compress' => true,
            ],
        ];
        $context = new Context(
            $input,
            [], // resultSet
            [], // inputSet
            [
                'custom_loader' => $stepConfig,
            ] // configuration
        );

        $result = $this->loaderStep->process($context);

        $this->assertSame([
            'loaded_data' => $input,
            'destination' => '/custom/path',
            'config_used' => $stepConfig,
        ], $result->getResultByKey('custom_loader')); // Changed from 'test_loader' to 'custom_loader'
    }

    #[Test]
    public function itHandlesComplexInputData(): void
    {
        $input = [
            'transactions' => [
                [
                    'id' => 1,
                    'amount' => 100.50,
                    'date' => '2024-01-01',
                ],
                [
                    'id' => 2,
                    'amount' => 200.75,
                    'date' => '2024-01-02',
                ],
            ],
            'summary' => [
                'total_amount' => 301.25,
                'count' => 2,
            ],
        ];
        $context = new Context($input);

        $result = $this->loaderStep->process($context);

        $this->assertSame([
            'loaded_data' => $input,
            'destination' => null,
            'config_used' => [],
        ], $result->getResultByKey('test_loader'));
    }

    #[Test]
    public function itPreservesOtherContextConfiguration(): void
    {
        $originalInput = [
            'data' => 'test',
        ];
        $context = new Context(
            $originalInput,
            [], // resultSet
            [], // inputSet
            [
                'other_step' => [
                    'param' => 'value',
                ],
            ] // configuration
        );

        $result = $this->loaderStep->process($context);

        $this->assertSame([
            'param' => 'value',
        ], $result->getConfigurationValue('other_step'));
        $this->assertSame([
            'loaded_data' => $originalInput,
            'destination' => null,
            'config_used' => [],
        ], $result->getResultByKey('test_loader'));
    }

    #[Test]
    public function itGetsOrder(): void
    {
        $this->assertSame(0, $this->loaderStep->getOrder());
    }

    #[Test]
    public function itSetsOrder(): void
    {
        $this->loaderStep->setOrder(10);

        $this->assertSame(10, $this->loaderStep->getOrder());
    }
}

/**
 * Concrete implementation of AbstractLoaderStep for testing purposes
 */
class ConcreteLoaderStep extends AbstractLoaderStep
{
    protected string $code = 'test_loader';

    public function load(mixed $data, mixed $destination, array $configuration = []): mixed
    {
        return [
            'loaded_data' => $data,
            'destination' => $destination,
            'config_used' => $configuration,
        ];
    }
}
