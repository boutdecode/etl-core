<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

abstract class AbstractPipelineExecutionStatistic implements PipelineExecutionStatistic
{
    protected Pipeline $pipeline;

    protected PipelineHistoryStatusEnum $status;

    protected \DateTimeImmutable $startedAt;

    protected \DateTimeImmutable $finishedAt;

    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }

    public function getStatus(): PipelineHistoryStatusEnum
    {
        return $this->status;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): \DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getDurationSeconds(): float
    {
        return (float) ($this->finishedAt->getTimestamp() - $this->startedAt->getTimestamp());
    }
}
