<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExecutorStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractExecutorStepTest extends TestCase
{
    private ConcreteExecutorStep $executorStep;

    protected function setUp(): void
    {
        $this->executorStep = new ConcreteExecutorStep();
    }

    #[Test]
    public function itGetsCode(): void
    {
        $this->assertSame('test_executor', $this->executorStep->getCode());
    }

    #[Test]
    public function itGetsNameWhenSet(): void
    {
        $this->executorStep->setName('Custom Executor');
        $this->assertSame('Custom Executor', $this->executorStep->getName());
    }

    #[Test]
    public function itGetsCodeAsNameWhenNameNotSet(): void
    {
        $this->assertSame('test_executor', $this->executorStep->getName());
    }

    #[Test]
    public function itSetsAndGetsConfiguration(): void
    {
        $config = [
            'command' => 'app:my-command',
            'arguments' => [
                '--env' => 'test',
            ],
        ];

        $this->executorStep->setConfiguration($config);

        $this->assertSame($config, $this->executorStep->getConfiguration());
    }

    #[Test]
    public function itStartsWithEmptyConfiguration(): void
    {
        $this->assertSame([], $this->executorStep->getConfiguration());
    }

    #[Test]
    public function itProcessesContextWithCommandFromStepConfig(): void
    {
        $input = [
            'some' => 'data',
        ];
        $stepConfig = [
            'command' => 'app:my-command',
            'arguments' => [
                '--verbose' => true,
            ],
        ];
        $context = new Context(
            $input,
            [],
            [],
            [
                'test_executor' => $stepConfig,
            ],
        );

        $result = $this->executorStep->process($context);

        $this->assertInstanceOf(Context::class, $result);
        $this->assertSame([
            'executed_command' => 'app:my-command',
            'executed_arguments' => [
                '--verbose' => true,
            ],
            'config_used' => $stepConfig,
        ], $result->getResultByKey('test_executor'));
    }

    #[Test]
    public function itProcessesContextWithCommandFromDefaultConfig(): void
    {
        $this->executorStep->setConfiguration([
            'command' => 'app:default-command',
            'arguments' => [],
        ]);
        $context = new Context([
            'some' => 'data',
        ]);

        $result = $this->executorStep->process($context);

        $this->assertSame([
            'executed_command' => 'app:default-command',
            'executed_arguments' => [],
            'config_used' => [],
        ], $result->getResultByKey('test_executor'));
    }

    #[Test]
    public function itProcessesContextWithEmptyCommandWhenNoConfig(): void
    {
        $context = new Context([
            'some' => 'data',
        ]);

        $result = $this->executorStep->process($context);

        $this->assertSame([
            'executed_command' => '',
            'executed_arguments' => [],
            'config_used' => [],
        ], $result->getResultByKey('test_executor'));
    }

    #[Test]
    public function itPrioritizesStepConfigOverDefaultConfig(): void
    {
        $this->executorStep->setConfiguration([
            'command' => 'app:default-command',
        ]);
        $stepConfig = [
            'command' => 'app:override-command',
            'arguments' => [
                '--force' => true,
            ],
        ];
        $context = new Context(
            [],
            [],
            [],
            [
                'test_executor' => $stepConfig,
            ],
        );

        $result = $this->executorStep->process($context);

        $this->assertSame([
            'executed_command' => 'app:override-command',
            'executed_arguments' => [
                '--force' => true,
            ],
            'config_used' => $stepConfig,
        ], $result->getResultByKey('test_executor'));
    }

    #[Test]
    public function itProcessesContextWithEmptyConfigWhenStepConfigNotArray(): void
    {
        $context = new Context(
            [],
            [],
            [],
            [
                'test_executor' => 'not_an_array',
            ],
        );

        $result = $this->executorStep->process($context);

        $this->assertSame([
            'executed_command' => '',
            'executed_arguments' => [],
            'config_used' => [],
        ], $result->getResultByKey('test_executor'));
    }

    #[Test]
    public function itNormalizesNonArrayArgumentsToEmptyArray(): void
    {
        $context = new Context(
            [],
            [],
            [],
            [
                'test_executor' => [
                    'command' => 'app:cmd',
                    'arguments' => 'not_an_array',
                ],
            ],
        );

        $result = $this->executorStep->process($context);

        $this->assertSame([
            'executed_command' => 'app:cmd',
            'executed_arguments' => [],
            'config_used' => [
                'command' => 'app:cmd',
                'arguments' => 'not_an_array',
            ],
        ], $result->getResultByKey('test_executor'));
    }

    #[Test]
    public function itProcessesContextWithCustomName(): void
    {
        $this->executorStep->setName('custom_executor');
        $stepConfig = [
            'command' => 'app:cmd',
            'arguments' => [],
        ];
        $context = new Context(
            [],
            [],
            [],
            [
                'custom_executor' => $stepConfig,
            ],
        );

        $result = $this->executorStep->process($context);

        $this->assertSame([
            'executed_command' => 'app:cmd',
            'executed_arguments' => [],
            'config_used' => $stepConfig,
        ], $result->getResultByKey('custom_executor'));
    }

    #[Test]
    public function itGetsOrder(): void
    {
        $this->assertSame(0, $this->executorStep->getOrder());
    }

    #[Test]
    public function itSetsOrder(): void
    {
        $this->executorStep->setOrder(5);

        $this->assertSame(5, $this->executorStep->getOrder());
    }

    #[Test]
    public function itPreservesOtherContextConfiguration(): void
    {
        $context = new Context(
            [],
            [],
            [],
            [
                'other_step' => [
                    'param' => 'value',
                ],
            ],
        );

        $result = $this->executorStep->process($context);

        $this->assertSame([
            'param' => 'value',
        ], $result->getConfigurationValue('other_step'));
    }
}

/**
 * Concrete implementation of AbstractExecutorStep for testing purposes.
 */
class ConcreteExecutorStep extends AbstractExecutorStep
{
    protected string $code = 'test_executor';

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $configuration
     *
     * @return array<string, mixed>
     */
    public function execute(string $command, array $arguments, Context $context, array $configuration = []): array
    {
        return [
            'executed_command' => $command,
            'executed_arguments' => $arguments,
            'config_used' => $configuration,
        ];
    }
}
