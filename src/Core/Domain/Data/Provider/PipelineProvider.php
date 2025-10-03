<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;

interface PipelineProvider
{
    public function findPipelineByIdentifier(string $identifier): ?Pipeline;

    /**
     * @return Pipeline[]
     */
    public function findScheduledPipelines(): array;
}
