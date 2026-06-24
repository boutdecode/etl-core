<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\AbstractWorkflowStatistic;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowStatistic;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractWorkflowStatisticTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function initialStateShouldHaveZeroCounters(): void
    {
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $this->assertSame(0, $stat->getTotalCount());
        $this->assertSame(0, $stat->getSuccessCount());
        $this->assertSame(0, $stat->getFailureCount());
        $this->assertSame(0, $stat->getTotalDurationMs());
        $this->assertNull($stat->getMinDurationMs());
        $this->assertNull($stat->getMaxDurationMs());
        $this->assertNull($stat->getAverageDurationMs());
        $this->assertSame(0.0, $stat->getSuccessRate());
        $this->assertNull($stat->getLastRunAt());
        $this->assertNull($stat->getLastRunStatus());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function recordSuccessShouldIncrementCounters(): void
    {
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $stat->recordSuccess(1000);

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
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $stat->recordFailure(500);

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
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $stat->recordSuccess(1000);
        $stat->recordSuccess(2000);
        $stat->recordSuccess(3000);

        $this->assertSame(6000, $stat->getTotalDurationMs());
        $this->assertSame(1000, $stat->getMinDurationMs());
        $this->assertSame(3000, $stat->getMaxDurationMs());
        $this->assertSame(2000, $stat->getAverageDurationMs());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function recordMixedRunsShouldTrackMinMaxAcrossAllRuns(): void
    {
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $stat->recordSuccess(1500);
        $stat->recordFailure(500);
        $stat->recordSuccess(2500);

        $this->assertSame(3, $stat->getTotalCount());
        $this->assertSame(2, $stat->getSuccessCount());
        $this->assertSame(1, $stat->getFailureCount());
        $this->assertSame(500, $stat->getMinDurationMs());
        $this->assertSame(2500, $stat->getMaxDurationMs());
        $this->assertSame((int) round(4500 / 3), $stat->getAverageDurationMs());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getSuccessRateShouldReturnCorrectRatio(): void
    {
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $stat->recordSuccess(1000);
        $stat->recordSuccess(1000);
        $stat->recordFailure(1000);

        $this->assertEqualsWithDelta(2 / 3, $stat->getSuccessRate(), 0.000001);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getSuccessRateShouldReturnZeroWhenNoRuns(): void
    {
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $this->assertSame(0.0, $stat->getSuccessRate());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getAverageDurationMsShouldReturnNullWhenNoRuns(): void
    {
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $this->assertNull($stat->getAverageDurationMs());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function lastRunStatusShouldReflectMostRecentRun(): void
    {
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $stat->recordSuccess(1000);
        $this->assertSame(PipelineHistoryStatusEnum::COMPLETED, $stat->getLastRunStatus());

        $stat->recordFailure(1000);
        $this->assertSame(PipelineHistoryStatusEnum::FAILED, $stat->getLastRunStatus());

        $stat->recordSuccess(1000);
        $this->assertSame(PipelineHistoryStatusEnum::COMPLETED, $stat->getLastRunStatus());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function implementsWorkflowStatisticInterface(): void
    {
        $stat = $this->createStatistic($this->createMock(Workflow::class));

        $this->assertInstanceOf(WorkflowStatistic::class, $stat);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getWorkflowShouldReturnWorkflow(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $stat = $this->createStatistic($workflow);

        $this->assertSame($workflow, $stat->getWorkflow());
    }

    private function createStatistic(Workflow $workflow): AbstractWorkflowStatistic
    {
        return new class($workflow) extends AbstractWorkflowStatistic {
            public function __construct(Workflow $workflow)
            {
                $this->workflow = $workflow;
                $this->updatedAt = new \DateTimeImmutable();
            }
        };
    }
}
