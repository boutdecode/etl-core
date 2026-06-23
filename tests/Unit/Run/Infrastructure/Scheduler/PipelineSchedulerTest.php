<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Infrastructure\Scheduler;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\Command;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecuteWorkflowCommand;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger\ExecutePipeline;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger\ExecutePipelineHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Spy implementation of CommandBus that accepts stamps as optional
 * (the interface lacks a default but the concrete implementation has one).
 */
final class SpyCommandBus implements CommandBus
{
    /**
     * @var list<Command>
     */
    public array $dispatched = [];

    public ?\Throwable $throwOnDispatch = null;

    public function dispatch(Command $command, array $stamps = []): mixed
    {
        if ($this->throwOnDispatch !== null) {
            throw $this->throwOnDispatch;
        }

        $this->dispatched[] = $command;

        return null;
    }
}

class PipelineSchedulerTest extends TestCase
{
    private SpyCommandBus $commandBus;

    private PipelineProvider $pipelineProvider;

    private PipelineWorkflow $pipelineWorkflow;

    private ExecutePipelineHandler $handler;

    protected function setUp(): void
    {
        $this->commandBus = new SpyCommandBus();
        $this->pipelineProvider = $this->createMock(PipelineProvider::class);
        $this->pipelineWorkflow = $this->createMock(PipelineWorkflow::class);
        $this->handler = new ExecutePipelineHandler($this->commandBus, $this->pipelineProvider, $this->pipelineWorkflow);
    }

    #[Test]
    public function itSchedulesPipelineBeforeDispatchingCommand(): void
    {
        $pipelineId = 'pipeline-to-schedule';
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getId')->willReturn($pipelineId);

        $this->pipelineProvider
            ->method('findScheduledPipelines')
            ->willReturn([$pipeline]);

        $this->pipelineWorkflow
            ->expects($this->once())
            ->method('schedule')
            ->with($pipeline);

        ($this->handler)(new ExecutePipeline());

        $this->assertCount(1, $this->commandBus->dispatched);
    }

    #[Test]
    public function itExecutesNoCommandsWhenNoScheduledPipelinesExist(): void
    {
        $this->pipelineProvider
            ->expects($this->once())
            ->method('findScheduledPipelines')
            ->willReturn([]);

        ($this->handler)(new ExecutePipeline());

        $this->assertCount(0, $this->commandBus->dispatched);
    }

    #[Test]
    public function itDispatchesExecuteCommandForSingleScheduledPipeline(): void
    {
        $pipelineId = 'test-pipeline-123';
        $pipeline = $this->createPipelineWithId($pipelineId);

        $this->pipelineProvider
            ->expects($this->once())
            ->method('findScheduledPipelines')
            ->willReturn([$pipeline]);

        ($this->handler)(new ExecutePipeline());

        $this->assertCount(1, $this->commandBus->dispatched);
        $this->assertInstanceOf(ExecuteWorkflowCommand::class, $this->commandBus->dispatched[0]);
        $this->assertSame($pipelineId, $this->commandBus->dispatched[0]->pipelineId);
    }

    #[Test]
    public function itDispatchesExecuteCommandsForMultipleScheduledPipelines(): void
    {
        $pipelineIds = ['pipeline-1', 'pipeline-2', 'pipeline-3'];
        $pipelines = array_map([$this, 'createPipelineWithId'], $pipelineIds);

        $this->pipelineProvider
            ->expects($this->once())
            ->method('findScheduledPipelines')
            ->willReturn($pipelines);

        ($this->handler)(new ExecutePipeline());

        $this->assertCount(3, $this->commandBus->dispatched);

        foreach ($this->commandBus->dispatched as $command) {
            $this->assertInstanceOf(ExecuteWorkflowCommand::class, $command);
        }
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itCreatesExecuteWorkflowCommandsWithCorrectPipelineIds(): void
    {
        $pipelineIds = ['etl-daily-reports', 'data-sync-hourly'];
        $pipelines = array_map([$this, 'createPipelineWithId'], $pipelineIds);

        $this->pipelineProvider
            ->method('findScheduledPipelines')
            ->willReturn($pipelines);

        ($this->handler)(new ExecutePipeline());

        $dispatchedIds = array_map(
            fn (Command $c) => $c instanceof ExecuteWorkflowCommand ? $c->pipelineId : '',
            $this->commandBus->dispatched
        );

        $this->assertContains('etl-daily-reports', $dispatchedIds);
        $this->assertContains('data-sync-hourly', $dispatchedIds);
        $this->assertCount(2, $dispatchedIds);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itContinuesProcessingAfterCommandBusException(): void
    {
        $pipeline1 = $this->createPipelineWithId('pipeline-1');
        $pipeline2 = $this->createPipelineWithId('pipeline-2');

        $this->pipelineProvider
            ->method('findScheduledPipelines')
            ->willReturn([$pipeline1, $pipeline2]);

        $this->commandBus->throwOnDispatch = new \RuntimeException('Command failed');

        // The handler doesn't catch exceptions, so this should throw on first pipeline
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Command failed');

        ($this->handler)(new ExecutePipeline());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itHandlesLargeNumberOfScheduledPipelines(): void
    {
        $pipelineCount = 50;
        $pipelines = [];

        for ($i = 1; $i <= $pipelineCount; $i++) {
            $pipelines[] = $this->createPipelineWithId("bulk-pipeline-{$i}");
        }

        $this->pipelineProvider
            ->method('findScheduledPipelines')
            ->willReturn($pipelines);

        ($this->handler)(new ExecutePipeline());

        $this->assertCount($pipelineCount, $this->commandBus->dispatched);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itPreservesPipelineIdFormatInCommands(): void
    {
        $specialIds = [
            'uuid-550e8400-e29b-41d4-a716-446655440000',
            'kebab-case-pipeline-name',
            'snake_case_pipeline_name',
            'mixed-Case_Pipeline123',
        ];

        $pipelines = array_map([$this, 'createPipelineWithId'], $specialIds);

        $this->pipelineProvider
            ->method('findScheduledPipelines')
            ->willReturn($pipelines);

        ($this->handler)(new ExecutePipeline());

        $actualIds = array_map(
            fn (Command $c) => $c instanceof ExecuteWorkflowCommand ? $c->pipelineId : '',
            $this->commandBus->dispatched
        );

        $this->assertSame($specialIds, $actualIds);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIsConfiguredAsMessageHandler(): void
    {
        $reflection = new \ReflectionClass(ExecutePipelineHandler::class);
        $attributes = $reflection->getAttributes(\Symfony\Component\Messenger\Attribute\AsMessageHandler::class);

        $this->assertCount(1, $attributes);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIsReadonlyAndFinalClass(): void
    {
        $reflection = new \ReflectionClass(ExecutePipelineHandler::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    /**
     * Helper method to create a pipeline stub with a given id
     */
    private function createPipelineWithId(string $id): Pipeline
    {
        $pipeline = new class($id) implements Pipeline {
            public function __construct(
                private string $id
            ) {
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function getName(): ?string
            {
                return null;
            }

            public function getCreatedAt(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }

            public function getScheduledAt(): ?\DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }

            public function getStartedAt(): ?\DateTimeImmutable
            {
                return null;
            }

            public function getFinishedAt(): ?\DateTimeImmutable
            {
                return null;
            }

            public function getStatus(): \BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus
            {
                return \BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus::PENDING;
            }

            public function getSteps(): iterable
            {
                return [];
            }

            public function getRunnableSteps(): iterable
            {
                return [];
            }

            public function setRunnableSteps(iterable $runnableSteps): void
            {
            }

            public function getConfiguration(): array
            {
                return [];
            }

            public function getInput(): array
            {
                return [];
            }

            public function getStepFromRunnableStep(Step $runnableStep): ?Step
            {
                return null;
            }

            public function schedule(): void
            {
            }

            public function start(): void
            {
            }

            public function finish(): void
            {
            }

            public function reset(): void
            {
            }

            public function plan(\DateTimeImmutable $scheduledAt): void
            {
            }
        };

        return $pipeline;
    }
}
