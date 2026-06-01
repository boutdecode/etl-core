<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Specification;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\PlannedTask;

interface IsPlannedTaskExecutableSpecification
{
    public function isSatisfiedBy(PlannedTask $plannedTask): bool;
}
