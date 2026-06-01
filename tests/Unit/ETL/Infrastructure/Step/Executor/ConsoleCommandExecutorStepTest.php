<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\ETL\Infrastructure\Step\Executor;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExecutorStep;
use BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Executor\ConsoleCommandExecutorStep;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommandExecutorStepTest extends TestCase
{
    private Application $application;

    private ConsoleCommandExecutorStep $executorStep;

    protected function setUp(): void
    {
        $this->application = $this->createMock(Application::class);
        $this->executorStep = new ConsoleCommandExecutorStep($this->application);
    }

    #[Test]
    public function getCodeShouldReturnCorrectCode(): void
    {
        $this->assertSame('etl.executor.console_command', $this->executorStep->getCode());
    }

    #[Test]
    public function stepShouldImplementCorrectInterface(): void
    {
        $this->assertInstanceOf(AbstractExecutorStep::class, $this->executorStep);
    }

    #[Test]
    public function executeShouldRunCommandAndReturnExitCodeAndOutput(): void
    {
        $this->application->expects($this->once())->method('setAutoExit')->with(false);
        $this->application->expects($this->once())->method('setCatchExceptions')->with(false);
        $this->application
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (InputInterface $input, OutputInterface $output): int {
                $output->write('Command executed successfully');

                return 0;
            });

        $result = $this->executorStep->execute('app:my-command', [], new Context(null));

        $this->assertSame(0, $result['exitCode']);
        $this->assertSame('Command executed successfully', $result['output']);
    }

    #[Test]
    public function executeShouldPassArgumentsToCommand(): void
    {
        $capturedInput = null;

        $this->application->method('setAutoExit');
        $this->application->method('setCatchExceptions');
        $this->application
            ->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (InputInterface $input, OutputInterface $output) use (&$capturedInput): int {
                $capturedInput = $input;

                return 0;
            });

        $this->executorStep->execute('app:my-command', [
            '--env' => 'test',
            '--verbose' => true,
        ], new Context(null));

        $this->assertNotNull($capturedInput);
    }

    #[Test]
    public function executeShouldReturnNonZeroExitCodeOnFailure(): void
    {
        $this->application->method('setAutoExit');
        $this->application->method('setCatchExceptions');
        $this->application->method('run')->willReturn(1);

        $result = $this->executorStep->execute('app:failing-command', [], new Context(null));

        $this->assertSame(1, $result['exitCode']);
    }

    #[Test]
    public function executeShouldEnableCatchExceptionsWhenConfigured(): void
    {
        $this->application->method('setAutoExit');
        $this->application->expects($this->once())->method('setCatchExceptions')->with(true);
        $this->application->method('run')->willReturn(0);

        $this->executorStep->execute('app:cmd', [], new Context(null), [
            'catchExceptions' => true,
        ]);
    }

    #[Test]
    public function executeShouldUseCatchExceptionsFromDefaultConfigWhenNotInStepConfig(): void
    {
        $this->executorStep->setConfiguration([
            'catchExceptions' => true,
        ]);

        $this->application->method('setAutoExit');
        $this->application->expects($this->once())->method('setCatchExceptions')->with(true);
        $this->application->method('run')->willReturn(0);

        $this->executorStep->execute('app:cmd', [], new Context(null));
    }

    #[Test]
    public function executeShouldReturnOutputFromCommand(): void
    {
        $this->application->method('setAutoExit');
        $this->application->method('setCatchExceptions');
        $this->application
            ->method('run')
            ->willReturnCallback(function (InputInterface $input, OutputInterface $output): int {
                $output->writeln('Line 1');
                $output->writeln('Line 2');

                return 0;
            });

        $result = $this->executorStep->execute('app:cmd', [], new Context(null));

        $this->assertStringContainsString('Line 1', $result['output']);
        $this->assertStringContainsString('Line 2', $result['output']);
    }

    #[Test]
    public function processShouldCallExecuteWithCommandFromContext(): void
    {
        $this->application->method('setAutoExit');
        $this->application->method('setCatchExceptions');
        $this->application->method('run')->willReturn(0);

        $context = new Context(
            null,
            [],
            [],
            [
                'etl.executor.console_command' => [
                    'command' => 'app:my-command',
                    'arguments' => [
                        '--env' => 'prod',
                    ],
                ],
            ],
        );

        $result = $this->executorStep->process($context);

        $this->assertInstanceOf(Context::class, $result);
        $stepResult = $result->getResultByKey('etl.executor.console_command');
        $this->assertIsArray($stepResult);
        $this->assertArrayHasKey('exitCode', $stepResult);
        $this->assertArrayHasKey('output', $stepResult);
        $this->assertSame(0, $stepResult['exitCode']);
    }
}
