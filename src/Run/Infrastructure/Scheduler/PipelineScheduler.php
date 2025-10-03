<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecuteWorkflowCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask(expression: '* * * * *', schedule: 'etl')]
final readonly class PipelineScheduler
{
    public function __construct(
        private CommandBus $bus,
        private PipelineProvider $pipelineProvider,
    ) {
    }

    public function __invoke(): void
    {
        $pipelines = $this->pipelineProvider->findScheduledPipelines();

        foreach ($pipelines as $pipeline) {
            $this->bus->dispatch(new ExecuteWorkflowCommand($pipeline->getId()));
        }
    }
}
