<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

abstract class AbstractWorkflowExecutionStatistic implements WorkflowExecutionStatistic
{
    protected Workflow $workflow;

    protected PipelineHistoryStatusEnum $status;

    protected \DateTimeImmutable $startedAt;

    protected \DateTimeImmutable $finishedAt;

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
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

    public function getDurationMs(): int
    {
        return (int) round(
            ((float) $this->finishedAt->format('U.u') - (float) $this->startedAt->format('U.u')) * 1000
        );
    }
}
