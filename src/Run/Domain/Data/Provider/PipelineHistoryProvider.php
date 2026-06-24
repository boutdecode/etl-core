<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Data\Provider;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\PipelineHistory;

interface PipelineHistoryProvider
{
    /**
     * @return iterable<PipelineHistory>
     */
    public function findByPipeline(Pipeline $pipeline, int $limit = 100): iterable;

    /**
     * @return iterable<PipelineHistory>
     */
    public function findByPipelineBetween(
        Pipeline $pipeline,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): iterable;
}
