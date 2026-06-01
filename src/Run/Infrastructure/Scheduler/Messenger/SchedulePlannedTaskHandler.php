<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PlannedTaskProvider;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecutePlannedTaskCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SchedulePlannedTaskHandler
{
    public function __construct(
        private PlannedTaskProvider $plannedTaskProvider,
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(SchedulePlannedTask $schedulePlannedTask): void
    {
        foreach ($this->plannedTaskProvider->findScheduled() as $task) {
            $this->commandBus->dispatch(new ExecutePlannedTaskCommand($task->getId()));
        }
    }
}
