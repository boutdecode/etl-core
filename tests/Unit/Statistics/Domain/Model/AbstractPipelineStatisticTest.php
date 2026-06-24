<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\AbstractPipelineStatistic;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineStatistic;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractPipelineStatisticTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function initialStateShouldHaveZeroCounters(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $this->assertSame(0, $stat->getTotalCount());
        $this->assertSame(0, $stat->getSuccessCount());
        $this->assertSame(0, $stat->getFailureCount());
        $this->assertSame(0.0, $stat->getTotalDurationSeconds());
        $this->assertNull($stat->getMinDurationSeconds());
        $this->assertNull($stat->getMaxDurationSeconds());
        $this->assertNull($stat->getAverageDurationSeconds());
        $this->assertSame(0.0, $stat->getSuccessRate());
        $this->assertNull($stat->getLastRunAt());
        $this->assertNull($stat->getLastRunStatus());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function recordSuccessShouldIncrementCounters(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $stat->recordSuccess(10.0);

        $this->assertSame(1, $stat->getTotalCount());
        $this->assertSame(1, $stat->getSuccessCount());
        $this->assertSame(0, $stat->getFailureCount());
        $this->assertSame(PipelineHistoryStatusEnum::COMPLETED, $stat->getLastRunStatus());
        $this->assertNotNull($stat->getLastRunAt());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function recordFailureShouldIncrementCounters(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $stat->recordFailure(5.0);

        $this->assertSame(1, $stat->getTotalCount());
        $this->assertSame(0, $stat->getSuccessCount());
        $this->assertSame(1, $stat->getFailureCount());
        $this->assertSame(PipelineHistoryStatusEnum::FAILED, $stat->getLastRunStatus());
        $this->assertNotNull($stat->getLastRunAt());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function recordSuccessShouldUpdateDurationStats(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $stat->recordSuccess(10.0);
        $stat->recordSuccess(20.0);
        $stat->recordSuccess(30.0);

        $this->assertSame(60.0, $stat->getTotalDurationSeconds());
        $this->assertSame(10.0, $stat->getMinDurationSeconds());
        $this->assertSame(30.0, $stat->getMaxDurationSeconds());
        $this->assertSame(20.0, $stat->getAverageDurationSeconds());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function recordMixedRunsShouldTrackMinMaxAcrossAllRuns(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $stat->recordSuccess(15.0);
        $stat->recordFailure(5.0);
        $stat->recordSuccess(25.0);

        $this->assertSame(3, $stat->getTotalCount());
        $this->assertSame(2, $stat->getSuccessCount());
        $this->assertSame(1, $stat->getFailureCount());
        $this->assertSame(5.0, $stat->getMinDurationSeconds());
        $this->assertSame(25.0, $stat->getMaxDurationSeconds());
        $this->assertSame(45.0 / 3, $stat->getAverageDurationSeconds());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getSuccessRateShouldReturnCorrectRatio(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $stat->recordSuccess(10.0);
        $stat->recordSuccess(10.0);
        $stat->recordFailure(10.0);

        $this->assertEqualsWithDelta(2 / 3, $stat->getSuccessRate(), 0.000001);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getSuccessRateShouldReturnZeroWhenNoRuns(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $this->assertSame(0.0, $stat->getSuccessRate());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getAverageDurationShouldReturnNullWhenNoRuns(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $this->assertNull($stat->getAverageDurationSeconds());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function lastRunStatusShouldReflectMostRecentRun(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $stat->recordSuccess(10.0);
        $this->assertSame(PipelineHistoryStatusEnum::COMPLETED, $stat->getLastRunStatus());

        $stat->recordFailure(10.0);
        $this->assertSame(PipelineHistoryStatusEnum::FAILED, $stat->getLastRunStatus());

        $stat->recordSuccess(10.0);
        $this->assertSame(PipelineHistoryStatusEnum::COMPLETED, $stat->getLastRunStatus());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function implementsPipelineStatisticInterface(): void
    {
        $stat = $this->createStatistic($this->createMock(Pipeline::class));

        $this->assertInstanceOf(PipelineStatistic::class, $stat);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getPipelineShouldReturnPipeline(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $stat = $this->createStatistic($pipeline);

        $this->assertSame($pipeline, $stat->getPipeline());
    }

    private function createStatistic(Pipeline $pipeline): AbstractPipelineStatistic
    {
        return new class($pipeline) extends AbstractPipelineStatistic {
            public function __construct(Pipeline $pipeline)
            {
                $this->pipeline = $pipeline;
                $this->updatedAt = new \DateTimeImmutable();
            }
        };
    }
}
