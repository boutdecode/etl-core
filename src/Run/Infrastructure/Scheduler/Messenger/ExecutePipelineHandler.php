<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecuteWorkflowCommand;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ExecutePipelineHandler
{
    public function __construct(
        private CommandBus $bus,
        private PipelineProvider $pipelineProvider,
        private PipelineWorkflow $pipelineWorkflow,
    ) {
    }

    public function __invoke(ExecutePipeline $message): void
    {
        $pipelines = $this->pipelineProvider->findScheduledPipelines();

        foreach ($pipelines as $pipeline) {
            $this->pipelineWorkflow->schedule($pipeline);
            $this->bus->dispatch(new ExecuteWorkflowCommand($pipeline->getId()));
        }
    }
}
