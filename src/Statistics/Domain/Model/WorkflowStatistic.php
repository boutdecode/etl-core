<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

interface WorkflowStatistic
{
    public function getWorkflow(): Workflow;

    public function getTotalCount(): int;

    public function getSuccessCount(): int;

    public function getFailureCount(): int;

    public function getTotalDurationMs(): int;

    public function getMinDurationMs(): ?int;

    public function getMaxDurationMs(): ?int;

    public function getAverageDurationMs(): ?int;

    public function getSuccessRate(): float;

    public function getLastRunAt(): ?\DateTimeImmutable;

    public function getLastRunStatus(): ?PipelineHistoryStatusEnum;

    public function getUpdatedAt(): \DateTimeImmutable;

    public function recordSuccess(int $durationMs): void;

    public function recordFailure(int $durationMs): void;
}
