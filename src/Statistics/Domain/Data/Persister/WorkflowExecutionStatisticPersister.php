<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowExecutionStatistic;

interface WorkflowExecutionStatisticPersister
{
    public function create(WorkflowExecutionStatistic $workflowExecutionStatistic): WorkflowExecutionStatistic;
}
