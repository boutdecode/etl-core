<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Runner;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PlannedTaskPersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\PipelineFactory;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\PlannedTask;
use BoutDeCode\ETLCoreBundle\Run\Domain\Scheduler\ExpressionScheduler;

final readonly class DefaultPlannedTaskRunner implements PlannedTaskRunner
{
    public function __construct(
        private ExpressionScheduler $expressionScheduler,
        private PipelineFactory $pipelineFactory,
        private PipelinePersister $pipelinePersister,
        private PlannedTaskPersister $plannedTaskPersister,
    ) {
    }

    public function run(PlannedTask $plannedTask): void
    {
        $pipeline = $this->pipelineFactory->createFromWorkflow(
            $plannedTask->getWorkflow(),
            $plannedTask->getConfiguration(),
            $plannedTask->getInput(),
        );

        $pipeline->plan($this->expressionScheduler->getNextScheduleFromExpression($plannedTask->getSchedule()));

        $plannedTask->setPipeline($pipeline);

        $this->pipelinePersister->create($pipeline);
        $this->plannedTaskPersister->save($plannedTask);
    }
}
