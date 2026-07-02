<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Infrastructure\Scheduler;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\Command;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandBus;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\PurgePipelineCommand;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger\PurgeOldPipelines;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger\PurgeOldPipelinesHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Spy implementation of CommandBus that accepts stamps as optional
 * (the interface lacks a default but the concrete implementation has one).
 */
final class SpyCommandBusForPurgeOldPipelines implements CommandBus
{
    /**
     * @var list<Command>
     */
    public array $dispatched = [];

    public function dispatch(Command $command, array $stamps = []): mixed
    {
        $this->dispatched[] = $command;

        return null;
    }
}

class PurgeOldPipelinesHandlerTest extends TestCase
{
    private SpyCommandBusForPurgeOldPipelines $commandBus;

    private PipelineProvider $pipelineProvider;

    protected function setUp(): void
    {
        $this->commandBus = new SpyCommandBusForPurgeOldPipelines();
        $this->pipelineProvider = $this->createMock(PipelineProvider::class);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itDoesNothingWhenPurgeIsDisabled(): void
    {
        $this->pipelineProvider
            ->expects($this->never())
            ->method('findPurgeablePipelines');

        $handler = new PurgeOldPipelinesHandler($this->pipelineProvider, $this->commandBus, false, 30);

        ($handler)(new PurgeOldPipelines());

        $this->assertCount(0, $this->commandBus->dispatched);
    }

    #[Test]
    public function itDispatchesNoCommandsWhenNoPurgeablePipelinesExist(): void
    {
        $this->pipelineProvider
            ->expects($this->once())
            ->method('findPurgeablePipelines')
            ->willReturn([]);

        $handler = new PurgeOldPipelinesHandler($this->pipelineProvider, $this->commandBus, true, 30);

        ($handler)(new PurgeOldPipelines());

        $this->assertCount(0, $this->commandBus->dispatched);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itDispatchesOneCommandPerPurgeablePipeline(): void
    {
        $pipelines = [
            $this->createPipelineWithId('pipeline-1'),
            $this->createPipelineWithId('pipeline-2'),
            $this->createPipelineWithId('pipeline-3'),
        ];

        $this->pipelineProvider
            ->method('findPurgeablePipelines')
            ->willReturn($pipelines);

        $handler = new PurgeOldPipelinesHandler($this->pipelineProvider, $this->commandBus, true, 30);

        ($handler)(new PurgeOldPipelines());

        $this->assertCount(3, $this->commandBus->dispatched);

        $dispatchedIds = array_map(
            fn (Command $c) => $c instanceof PurgePipelineCommand ? $c->pipelineId : '',
            $this->commandBus->dispatched
        );

        $this->assertSame(['pipeline-1', 'pipeline-2', 'pipeline-3'], $dispatchedIds);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itComputesThresholdFromRetentionDays(): void
    {
        $capturedThreshold = null;

        $this->pipelineProvider
            ->method('findPurgeablePipelines')
            ->willReturnCallback(function (\DateTimeImmutable $threshold) use (&$capturedThreshold) {
                $capturedThreshold = $threshold;

                return [];
            });

        $handler = new PurgeOldPipelinesHandler($this->pipelineProvider, $this->commandBus, true, 10);

        $before = new \DateTimeImmutable('-10 days');
        ($handler)(new PurgeOldPipelines());
        $after = new \DateTimeImmutable('-10 days');

        $this->assertNotNull($capturedThreshold);
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $capturedThreshold->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $capturedThreshold->getTimestamp());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIsDecoratedAsMessageHandler(): void
    {
        $reflection = new \ReflectionClass(PurgeOldPipelinesHandler::class);
        $attributes = $reflection->getAttributes(\Symfony\Component\Messenger\Attribute\AsMessageHandler::class);

        $this->assertCount(1, $attributes);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIsFinalAndReadonly(): void
    {
        $reflection = new \ReflectionClass(PurgeOldPipelinesHandler::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }

    private function createPipelineWithId(string $id): Pipeline
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getId')->willReturn($id);

        return $pipeline;
    }
}
