<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Runner;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\PlannedTask;

interface PlannedTaskRunner
{
    public function run(PlannedTask $plannedTask): void;
}
