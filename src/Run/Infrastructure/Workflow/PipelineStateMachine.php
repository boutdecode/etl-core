<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Workflow;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class PipelineStateMachine implements PipelineWorkflow
{
    public function __construct(
        private WorkflowInterface $pipelineLifecycleStateMachine,
    ) {
    }

    public function start(Pipeline $pipeline): void
    {
        $pipeline->start();

        $this->pipelineLifecycleStateMachine->apply($pipeline, 'start');
    }

    public function complete(Pipeline $pipeline): void
    {
        $pipeline->finish();

        $this->pipelineLifecycleStateMachine->apply($pipeline, 'complete');
    }

    public function fail(Pipeline $pipeline, \Throwable $throwable): void
    {
        $pipeline->finish();

        $this->pipelineLifecycleStateMachine->apply($pipeline, 'fail', [
            'error_message' => $throwable->getMessage(),
        ]);
    }

    public function reset(Pipeline $pipeline): void
    {
        $pipeline->reset();

        $this->pipelineLifecycleStateMachine->apply($pipeline, 'reset');
    }

    public function restart(Pipeline $pipeline): void
    {
        $pipeline->start();

        $this->pipelineLifecycleStateMachine->apply($pipeline, 'restart');
    }
}
