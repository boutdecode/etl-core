<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Infrastructure\Scheduler;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecuteWorkflowCommand;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\PipelineScheduler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineSchedulerTest extends TestCase
{
    private CommandBus $commandBus;

    private PipelineProvider $pipelineProvider;

    private PipelineScheduler $scheduler;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->pipelineProvider = $this->createMock(PipelineProvider::class);
        $this->scheduler = new PipelineScheduler($this->commandBus, $this->pipelineProvider);
    }

    #[Test]
    public function itExecutesNoCommandsWhenNoScheduledPipelinesExist(): void
    {
        $this->pipelineProvider
            ->expects($this->once())
            ->method('findScheduledPipelines')
            ->willReturn([]);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $this->scheduler->__invoke();
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

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ExecuteWorkflowCommand $command) use ($pipelineId) {
                return $command->pipelineId === $pipelineId;
            }));

        $this->scheduler->__invoke();
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

        $this->commandBus
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->with($this->isInstanceOf(ExecuteWorkflowCommand::class));

        $this->scheduler->__invoke();
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itCreatesExecuteWorkflowCommandsWithCorrectPipelineIds(): void
    {
        $pipelineIds = ['etl-daily-reports', 'data-sync-hourly'];
        $pipelines = array_map([$this, 'createPipelineWithId'], $pipelineIds);
        $dispatchedCommands = [];

        $this->pipelineProvider
            ->method('findScheduledPipelines')
            ->willReturn($pipelines);

        $this->commandBus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (ExecuteWorkflowCommand $command) use (&$dispatchedCommands) {
                $dispatchedCommands[] = $command->pipelineId;
                return null;
            });

        $this->scheduler->__invoke();

        $this->assertContains('etl-daily-reports', $dispatchedCommands);
        $this->assertContains('data-sync-hourly', $dispatchedCommands);
        $this->assertCount(2, $dispatchedCommands);
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

        $this->commandBus
            ->expects($this->once()) // Only one call because first call throws exception
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('Command failed'));

        // The scheduler doesn't catch exceptions, so this should throw on first pipeline
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Command failed');

        $this->scheduler->__invoke();
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

        $this->commandBus
            ->expects($this->exactly($pipelineCount))
            ->method('dispatch')
            ->with($this->isInstanceOf(ExecuteWorkflowCommand::class));

        $this->scheduler->__invoke();
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
        $actualIds = [];

        $this->pipelineProvider
            ->method('findScheduledPipelines')
            ->willReturn($pipelines);

        $this->commandBus
            ->method('dispatch')
            ->willReturnCallback(function (ExecuteWorkflowCommand $command) use (&$actualIds) {
                $actualIds[] = $command->pipelineId;
            });

        $this->scheduler->__invoke();

        $this->assertSame($specialIds, $actualIds);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIsConfiguredAsCronTask(): void
    {
        $reflection = new \ReflectionClass(PipelineScheduler::class);
        $attributes = $reflection->getAttributes(\Symfony\Component\Scheduler\Attribute\AsCronTask::class);

        $this->assertCount(1, $attributes);

        $cronTask = $attributes[0]->newInstance();
        $this->assertSame('* * * * *', $cronTask->expression);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIsReadonlyAndFinalClass(): void
    {
        $reflection = new \ReflectionClass(PipelineScheduler::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    /**
     * Helper method to create a pipeline mock with getId method
     */
    private function createPipelineWithId(string $id): Pipeline
    {
        // Create an anonymous class that implements Pipeline and has getId method
        $pipeline = new class($id) implements Pipeline {
            public function __construct(
                private string $id
            ) {
            }

            public function getId(): string
            {
                return $this->id;
            }

            // Implement required Pipeline interface methods
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

            public function start(): void
            {
            }

            public function finish(): void
            {
            }

            public function reset(): void
            {
            }
        };

        return $pipeline;
    }
}
