<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;

interface StepPersister
{
    public function create(Step $step): Step;
}
