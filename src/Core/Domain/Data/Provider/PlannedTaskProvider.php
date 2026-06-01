<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\PlannedTask;

interface PlannedTaskProvider
{
    public function findByIdentifier(string $identifier): ?PlannedTask;

    /**
     * @return PlannedTask[]
     */
    public function findScheduled(): array;
}
