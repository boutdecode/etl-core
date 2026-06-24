<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Statistics\Application\Operation\Query;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query\GetPipelineStatisticQuery;
use BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query\GetPipelineStatisticQueryHandler;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\PipelineStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineStatistic;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GetPipelineStatisticQueryHandlerTest extends TestCase
{
    private PipelineProvider $pipelineProvider;

    private PipelineStatisticProvider $statisticProvider;

    private GetPipelineStatisticQueryHandler $handler;

    protected function setUp(): void
    {
        $this->pipelineProvider = $this->createMock(PipelineProvider::class);
        $this->statisticProvider = $this->createMock(PipelineStatisticProvider::class);
        $this->handler = new GetPipelineStatisticQueryHandler(
            $this->pipelineProvider,
            $this->statisticProvider,
        );
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function invokeShouldReturnStatisticWhenPipelineAndStatisticExist(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $statistic = $this->createMock(PipelineStatistic::class);

        $this->pipelineProvider->method('findPipelineByIdentifier')
            ->with('pipeline-1')
            ->willReturn($pipeline);

        $this->statisticProvider->method('findByPipeline')
            ->with($pipeline)
            ->willReturn($statistic);

        $result = ($this->handler)(new GetPipelineStatisticQuery('pipeline-1'));

        $this->assertSame($statistic, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function invokeShouldReturnNullWhenPipelineNotFound(): void
    {
        $this->pipelineProvider->method('findPipelineByIdentifier')->willReturn(null);
        $this->statisticProvider->expects($this->never())->method('findByPipeline');

        $result = ($this->handler)(new GetPipelineStatisticQuery('unknown'));

        $this->assertNull($result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function invokeShouldReturnNullWhenNoStatisticYet(): void
    {
        $pipeline = $this->createMock(Pipeline::class);

        $this->pipelineProvider->method('findPipelineByIdentifier')->willReturn($pipeline);
        $this->statisticProvider->method('findByPipeline')->willReturn(null);

        $result = ($this->handler)(new GetPipelineStatisticQuery('pipeline-1'));

        $this->assertNull($result);
    }
}
