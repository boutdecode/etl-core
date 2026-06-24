<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

abstract class AbstractPipelineStatistic implements PipelineStatistic
{
    protected Pipeline $pipeline;

    protected int $totalCount = 0;

    protected int $successCount = 0;

    protected int $failureCount = 0;

    protected float $totalDurationSeconds = 0.0;

    protected ?float $minDurationSeconds = null;

    protected ?float $maxDurationSeconds = null;

    protected ?\DateTimeImmutable $lastRunAt = null;

    protected ?PipelineHistoryStatusEnum $lastRunStatus = null;

    protected \DateTimeImmutable $updatedAt;

    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
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

    public function getTotalDurationSeconds(): float
    {
        return $this->totalDurationSeconds;
    }

    public function getMinDurationSeconds(): ?float
    {
        return $this->minDurationSeconds;
    }

    public function getMaxDurationSeconds(): ?float
    {
        return $this->maxDurationSeconds;
    }

    public function getAverageDurationSeconds(): ?float
    {
        if ($this->totalCount === 0) {
            return null;
        }

        return $this->totalDurationSeconds / $this->totalCount;
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

    public function recordSuccess(float $durationSeconds): void
    {
        $this->totalCount++;
        $this->successCount++;
        $this->updateDuration($durationSeconds);
        $this->lastRunAt = new \DateTimeImmutable();
        $this->lastRunStatus = PipelineHistoryStatusEnum::COMPLETED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function recordFailure(float $durationSeconds): void
    {
        $this->totalCount++;
        $this->failureCount++;
        $this->updateDuration($durationSeconds);
        $this->lastRunAt = new \DateTimeImmutable();
        $this->lastRunStatus = PipelineHistoryStatusEnum::FAILED;
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function updateDuration(float $durationSeconds): void
    {
        $this->totalDurationSeconds += $durationSeconds;

        if ($this->minDurationSeconds === null || $durationSeconds < $this->minDurationSeconds) {
            $this->minDurationSeconds = $durationSeconds;
        }

        if ($this->maxDurationSeconds === null || $durationSeconds > $this->maxDurationSeconds) {
            $this->maxDurationSeconds = $durationSeconds;
        }
    }
}
