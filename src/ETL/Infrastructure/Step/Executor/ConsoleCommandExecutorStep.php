<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\ETL\Infrastructure\Step\Executor;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\AbstractExecutorStep;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsExecutableStep(
    code: 'etl.executor.console_command',
    configurationDescription: [
        'command' => 'The Symfony console command name to execute (e.g. "app:my-command")',
        'arguments' => 'Associative array of arguments and options to pass to the command',
        'catchExceptions' => 'Whether the application should catch exceptions (default: false)',
    ],
)]
final class ConsoleCommandExecutorStep extends AbstractExecutorStep
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $configuration
     *
     * @return array{exitCode: int, output: string}
     * @throws \Exception
     */
    public function execute(string $command, array $arguments, Context $context, array $configuration = []): array
    {
        $catchExceptions = $configuration['catchExceptions'] ?? $this->configuration['catchExceptions'] ?? false;

        $input = new ArrayInput(array_merge([
            'command' => $command,
        ], $arguments));
        $output = new BufferedOutput();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->setCatchExceptions((bool) $catchExceptions);

        $exitCode = $application->run($input, $output);

        return [
            'exitCode' => $exitCode,
            'output' => $output->fetch(),
        ];
    }
}
