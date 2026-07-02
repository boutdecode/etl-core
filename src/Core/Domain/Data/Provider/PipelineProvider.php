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

    /**
     * Returns pipelines in a terminal state (COMPLETED or FAILED) whose
     * finishedAt is strictly older than $before.
     *
     * @return Pipeline[]
     */
    public function findPurgeablePipelines(\DateTimeImmutable $before): array;
}
