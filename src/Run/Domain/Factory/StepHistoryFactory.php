<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Factory;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory;

interface StepHistoryFactory
{
    public function create(
        Step $step,
        StepHistoryStatusEnum $status,
        mixed $input,
        mixed $result,
    ): StepHistory;
}
