<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory;

interface StepHistoryPersister
{
    public function create(StepHistory $stepHistory): StepHistory;
}
