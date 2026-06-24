<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

interface WorkflowExecutionStatistic
{
    public function getWorkflow(): Workflow;

    public function getStatus(): PipelineHistoryStatusEnum;

    public function getStartedAt(): \DateTimeImmutable;

    public function getFinishedAt(): \DateTimeImmutable;

    public function getDurationMs(): int;
}
