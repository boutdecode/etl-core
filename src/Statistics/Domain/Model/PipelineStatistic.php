<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

interface PipelineStatistic
{
    public function getPipeline(): Pipeline;

    public function getTotalCount(): int;

    public function getSuccessCount(): int;

    public function getFailureCount(): int;

    public function getTotalDurationSeconds(): float;

    public function getMinDurationSeconds(): ?float;

    public function getMaxDurationSeconds(): ?float;

    public function getAverageDurationSeconds(): ?float;

    public function getSuccessRate(): float;

    public function getLastRunAt(): ?\DateTimeImmutable;

    public function getLastRunStatus(): ?PipelineHistoryStatusEnum;

    public function getUpdatedAt(): \DateTimeImmutable;

    public function recordSuccess(float $durationSeconds): void;

    public function recordFailure(float $durationSeconds): void;
}
