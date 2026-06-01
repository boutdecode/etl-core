<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PlannedTaskProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\PlannedTask;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandHandler;
use BoutDeCode\ETLCoreBundle\Run\Domain\Runner\PlannedTaskRunner;
use BoutDeCode\ETLCoreBundle\Run\Domain\Specification\IsPlannedTaskExecutableSpecification;
use Webmozart\Assert\Assert;

final readonly class ExecutePlannedTaskCommandHandler implements CommandHandler
{
    public function __construct(
        private PlannedTaskProvider $plannedTaskProvider,
        private PlannedTaskRunner $plannedTaskRunner,
        private IsPlannedTaskExecutableSpecification $isPlannedTaskExecutable,
    ) {
    }

    public function __invoke(ExecutePlannedTaskCommand $command): void
    {
        $plannedTask = $this->plannedTaskProvider->findByIdentifier($command->plannedTaskId);

        Assert::isInstanceOf($plannedTask, PlannedTask::class);

        if (! $this->isPlannedTaskExecutable->isSatisfiedBy($plannedTask)) {
            return;
        }

        $this->plannedTaskRunner->run($plannedTask);
    }
}
