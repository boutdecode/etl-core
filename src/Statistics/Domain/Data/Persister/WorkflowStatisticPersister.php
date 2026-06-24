<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowStatistic;

interface WorkflowStatisticPersister
{
    public function create(WorkflowStatistic $workflowStatistic): WorkflowStatistic;

    public function save(WorkflowStatistic $workflowStatistic): WorkflowStatistic;
}
