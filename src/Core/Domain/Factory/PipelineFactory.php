<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Factory;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;

interface PipelineFactory
{
    /**
     * @param Step[] $steps
     * @param array<string, mixed> $configuration
     */
    public function create(
        array $steps = [],
        array $configuration = []
    ): Pipeline;

    /**
     * @param array<string, mixed> $overrideConfiguration
     * @param array<string, mixed> $input
     */
    public function createFromWorkflowId(
        string $workflowId,
        array $overrideConfiguration = [],
        array $input = [],
    ): Pipeline;
}
