<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Infrastructure\Workflow;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Workflow\PipelineStateMachine;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\WorkflowInterface;

final class PipelineStateMachineTest extends TestCase
{
    private WorkflowInterface $workflowMock;

    private Pipeline $pipelineMock;

    protected function setUp(): void
    {
        $this->workflowMock = $this->createMock(WorkflowInterface::class);
        $this->pipelineMock = $this->createMock(Pipeline::class);
    }

    #[Test]
    public function startAppliesStartTransition(): void
    {
        $this->workflowMock->expects($this->once())
            ->method('apply')
            ->with($this->pipelineMock, 'start');

        $stateMachine = new PipelineStateMachine($this->workflowMock);
        $stateMachine->start($this->pipelineMock);
    }

    #[Test]
    public function completeAppliesCompleteTransition(): void
    {
        $this->workflowMock->expects($this->once())
            ->method('apply')
            ->with($this->pipelineMock, 'complete');

        $stateMachine = new PipelineStateMachine($this->workflowMock);
        $stateMachine->complete($this->pipelineMock);
    }

    #[Test]
    public function failAppliesFailTransition(): void
    {
        $throwable = new \RuntimeException('Something went wrong');

        $this->workflowMock->expects($this->once())
            ->method('apply')
            ->with($this->pipelineMock, 'fail', [
                'error_message' => 'Something went wrong',
            ]);

        $stateMachine = new PipelineStateMachine($this->workflowMock);
        $stateMachine->fail($this->pipelineMock, $throwable);
    }

    #[Test]
    public function resetAppliesResetTransition(): void
    {
        $this->workflowMock->expects($this->once())
            ->method('apply')
            ->with($this->pipelineMock, 'reset');

        $stateMachine = new PipelineStateMachine($this->workflowMock);
        $stateMachine->reset($this->pipelineMock);
    }

    #[Test]
    public function restartAppliesRestartTransitionNotReset(): void
    {
        $this->workflowMock->expects($this->once())
            ->method('apply')
            ->with($this->pipelineMock, 'restart');

        $stateMachine = new PipelineStateMachine($this->workflowMock);
        $stateMachine->restart($this->pipelineMock);
    }

    #[Test]
    public function restartCallsPipelineStart(): void
    {
        $this->pipelineMock->expects($this->once())->method('start');
        $this->workflowMock->method('apply');

        $stateMachine = new PipelineStateMachine($this->workflowMock);
        $stateMachine->restart($this->pipelineMock);
    }
}
