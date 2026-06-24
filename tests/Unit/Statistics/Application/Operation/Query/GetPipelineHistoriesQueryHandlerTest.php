<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Statistics\Application\Operation\Query;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Provider\PipelineHistoryProvider;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\PipelineHistory;
use BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query\GetPipelineHistoriesQuery;
use BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query\GetPipelineHistoriesQueryHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GetPipelineHistoriesQueryHandlerTest extends TestCase
{
    private PipelineProvider $pipelineProvider;

    private PipelineHistoryProvider $historyProvider;

    private GetPipelineHistoriesQueryHandler $handler;

    protected function setUp(): void
    {
        $this->pipelineProvider = $this->createMock(PipelineProvider::class);
        $this->historyProvider = $this->createMock(PipelineHistoryProvider::class);
        $this->handler = new GetPipelineHistoriesQueryHandler(
            $this->pipelineProvider,
            $this->historyProvider,
        );
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function invokeShouldReturnHistoriesWhenPipelineExists(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $history1 = $this->createMock(PipelineHistory::class);
        $history2 = $this->createMock(PipelineHistory::class);
        $from = new \DateTimeImmutable('2026-01-01');
        $to = new \DateTimeImmutable('2026-01-31');

        $this->pipelineProvider->method('findPipelineByIdentifier')
            ->with('pipeline-1')
            ->willReturn($pipeline);

        $this->historyProvider->method('findByPipelineBetween')
            ->with($pipeline, $from, $to)
            ->willReturn([$history1, $history2]);

        $result = ($this->handler)(new GetPipelineHistoriesQuery('pipeline-1', $from, $to));

        $this->assertSame([$history1, $history2], $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function invokeShouldReturnEmptyArrayWhenPipelineNotFound(): void
    {
        $this->pipelineProvider->method('findPipelineByIdentifier')->willReturn(null);
        $this->historyProvider->expects($this->never())->method('findByPipelineBetween');

        $result = ($this->handler)(new GetPipelineHistoriesQuery(
            'unknown',
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-01-31'),
        ));

        $this->assertSame([], $result);
    }
}
