<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Factory;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;

interface WorkflowFactory
{
    /**
     * @param array<string, mixed> $configuration
     * @param array<string, mixed> $stepConfiguration
     */
    public function create(
        string $name,
        array $configuration = [],
        array $stepConfiguration = [],
        ?string $description = null,
    ): Workflow;
}
