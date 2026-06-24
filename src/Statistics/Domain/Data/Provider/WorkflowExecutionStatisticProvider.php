<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowExecutionStatistic;

interface WorkflowExecutionStatisticProvider
{
    /**
     * @return iterable<WorkflowExecutionStatistic>
     */
    public function findByWorkflow(Workflow $workflow, int $limit = 100): iterable;

    /**
     * @return iterable<WorkflowExecutionStatistic>
     */
    public function findByWorkflowBetween(
        Workflow $workflow,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): iterable;
}
