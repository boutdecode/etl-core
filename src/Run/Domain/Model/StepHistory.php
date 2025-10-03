<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Model;

use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;

interface StepHistory
{
    public function getStatus(): StepHistoryStatusEnum;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getInput(): mixed;

    public function getResult(): mixed;
}
