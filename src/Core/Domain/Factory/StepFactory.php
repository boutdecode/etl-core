<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Factory;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;

interface StepFactory
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function create(
        string $code,
        ?string $name = null,
        array $configuration = []
    ): Step;
}
