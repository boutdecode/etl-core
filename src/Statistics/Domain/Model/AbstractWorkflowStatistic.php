<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

abstract class AbstractWorkflowStatistic implements WorkflowStatistic
{
    protected Workflow $workflow;

    protected int $totalCount = 0;

    protected int $successCount = 0;

    protected int $failureCount = 0;

    protected int $totalDurationMs = 0;

    protected ?int $minDurationMs = null;

    protected ?int $maxDurationMs = null;

    protected ?\DateTimeImmutable $lastRunAt = null;

    protected ?PipelineHistoryStatusEnum $lastRunStatus = null;

    protected \DateTimeImmutable $updatedAt;

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    public function getTotalDurationMs(): int
    {
        return $this->totalDurationMs;
    }

    public function getMinDurationMs(): ?int
    {
        return $this->minDurationMs;
    }

    public function getMaxDurationMs(): ?int
    {
        return $this->maxDurationMs;
    }

    public function getAverageDurationMs(): ?int
    {
        if ($this->totalCount === 0) {
            return null;
        }

        return (int) round($this->totalDurationMs / $this->totalCount);
    }

    public function getSuccessRate(): float
    {
        if ($this->totalCount === 0) {
            return 0.0;
        }

        return $this->successCount / $this->totalCount;
    }

    public function getLastRunAt(): ?\DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    public function getLastRunStatus(): ?PipelineHistoryStatusEnum
    {
        return $this->lastRunStatus;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function recordSuccess(int $durationMs): void
    {
        $this->totalCount++;
        $this->successCount++;
        $this->updateDuration($durationMs);
        $this->lastRunAt = new \DateTimeImmutable();
        $this->lastRunStatus = PipelineHistoryStatusEnum::COMPLETED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function recordFailure(int $durationMs): void
    {
        $this->totalCount++;
        $this->failureCount++;
        $this->updateDuration($durationMs);
        $this->lastRunAt = new \DateTimeImmutable();
        $this->lastRunStatus = PipelineHistoryStatusEnum::FAILED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function updateDuration(int $durationMs): void
    {
        $this->totalDurationMs += $durationMs;

        if ($this->minDurationMs === null || $durationMs < $this->minDurationMs) {
            $this->minDurationMs = $durationMs;
        }

        if ($this->maxDurationMs === null || $durationMs > $this->maxDurationMs) {
            $this->maxDurationMs = $durationMs;
        }
    }
}
