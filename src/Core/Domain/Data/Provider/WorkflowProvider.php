<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;

interface WorkflowProvider
{
    public function findWorkflowByIdentifier(string $identifier): ?Workflow;
}
