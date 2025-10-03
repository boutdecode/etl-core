<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;

interface WorkflowPersister
{
    public function create(Workflow $workflow): Workflow;
}
