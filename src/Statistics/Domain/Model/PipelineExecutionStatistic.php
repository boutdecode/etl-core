<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

interface PipelineExecutionStatistic
{
    public function getPipeline(): Pipeline;

    public function getStatus(): PipelineHistoryStatusEnum;

    public function getStartedAt(): \DateTimeImmutable;

    public function getFinishedAt(): \DateTimeImmutable;

    public function getDurationSeconds(): float;
}
