<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\PlannedTask;

interface PlannedTaskPersister
{
    public function create(PlannedTask $plannedTask): PlannedTask;

    public function save(PlannedTask $plannedTask): PlannedTask;
}
