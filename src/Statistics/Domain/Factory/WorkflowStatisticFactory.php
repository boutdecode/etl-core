<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowStatistic;

interface WorkflowStatisticFactory
{
    public function create(Workflow $workflow): WorkflowStatistic;
}
