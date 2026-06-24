<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowStatistic;

interface WorkflowStatisticProvider
{
    public function findByWorkflow(Workflow $workflow): ?WorkflowStatistic;

    /**
     * @return iterable<WorkflowStatistic>
     */
    public function findAll(): iterable;
}
