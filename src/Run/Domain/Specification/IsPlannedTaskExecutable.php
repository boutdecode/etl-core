<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Specification;

use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\PlannedTask;

class IsPlannedTaskExecutable implements IsPlannedTaskExecutableSpecification
{
    public function isSatisfiedBy(PlannedTask $plannedTask): bool
    {
        return $plannedTask->isEnabled() &&
            (
                $plannedTask->getPipeline() === null ||
                $plannedTask->getPipeline()->getStatus() !== PipelineStatus::PENDING
            )
        ;
    }
}
