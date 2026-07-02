<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Application\Operation\Command;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\PurgePipelineCommand;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\PurgePipelineCommandHandler;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\PipelineHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\StepHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Provider\PipelineHistoryProvider;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\PipelineHistory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

class PurgePipelineCommandHandlerTest extends TestCase
{
    private PipelineProvider $pipelineProvider;

    private PipelineHistoryProvider $pipelineHistoryProvider;

    private PipelineHistoryPersister $pipelineHistoryPersister;

    private StepHistoryPersister $stepHistoryPersister;

    private PipelinePersister $pipelinePersister;

    private PurgePipelineCommandHandler $handler;

    protected function setUp(): void
    {
        $this->pipelineProvider = $this->createMock(PipelineProvider::class);
        $this->pipelineHistoryProvider = $this->createMock(PipelineHistoryProvider::class);
        $this->pipelineHistoryPersister = $this->createMock(PipelineHistoryPersister::class);
        $this->stepHistoryPersister = $this->createMock(StepHistoryPersister::class);
        $this->pipelinePersister = $this->createMock(PipelinePersister::class);

        $this->handler = new PurgePipelineCommandHandler(
            $this->pipelineProvider,
            $this->pipelineHistoryProvider,
            $this->pipelineHistoryPersister,
            $this->stepHistoryPersister,
            $this->pipelinePersister
        );
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itDeletesStepHistoriesThenPipelineHistoriesThenPipeline(): void
    {
        $pipelineId = 'pipeline-1';
        $command = new PurgePipelineCommand($pipelineId);
        $pipeline = $this->createMock(Pipeline::class);

        $stepHistory1 = $this->createMock(StepHistory::class);
        $stepHistory2 = $this->createMock(StepHistory::class);

        $pipelineHistory = $this->createMock(PipelineHistory::class);
        $pipelineHistory->method('getStepHistories')->willReturn([$stepHistory1, $stepHistory2]);

        $this->pipelineProvider
            ->expects($this->once())
            ->method('findPipelineByIdentifier')
            ->with($pipelineId)
            ->willReturn($pipeline);

        $this->pipelineHistoryProvider
            ->expects($this->once())
            ->method('findByPipeline')
            ->with($pipeline)
            ->willReturn([$pipelineHistory]);

        $this->stepHistoryPersister
            ->expects($this->exactly(2))
            ->method('delete')
            ->with($this->logicalOr($stepHistory1, $stepHistory2));

        $this->pipelineHistoryPersister
            ->expects($this->once())
            ->method('delete')
            ->with($pipelineHistory);

        $this->pipelinePersister
            ->expects($this->once())
            ->method('delete')
            ->with($pipeline);

        ($this->handler)($command);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itDeletesPipelineWithNoHistory(): void
    {
        $pipelineId = 'pipeline-empty';
        $command = new PurgePipelineCommand($pipelineId);
        $pipeline = $this->createMock(Pipeline::class);

        $this->pipelineProvider
            ->method('findPipelineByIdentifier')
            ->willReturn($pipeline);

        $this->pipelineHistoryProvider
            ->method('findByPipeline')
            ->willReturn([]);

        $this->stepHistoryPersister
            ->expects($this->never())
            ->method('delete');

        $this->pipelineHistoryPersister
            ->expects($this->never())
            ->method('delete');

        $this->pipelinePersister
            ->expects($this->once())
            ->method('delete')
            ->with($pipeline);

        ($this->handler)($command);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itThrowsExceptionWhenPipelineNotFound(): void
    {
        $command = new PurgePipelineCommand('non-existent-pipeline');

        $this->pipelineProvider
            ->expects($this->once())
            ->method('findPipelineByIdentifier')
            ->willReturn(null);

        $this->pipelineHistoryProvider
            ->expects($this->never())
            ->method('findByPipeline');

        $this->pipelinePersister
            ->expects($this->never())
            ->method('delete');

        $this->expectException(InvalidArgumentException::class);

        ($this->handler)($command);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itImplementsCommandHandlerInterface(): void
    {
        $this->assertInstanceOf(\BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandHandler::class, $this->handler);
    }
}
