<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineExecutionStatistic;

interface PipelineExecutionStatisticProvider
{
    /**
     * @return iterable<PipelineExecutionStatistic>
     */
    public function findByPipeline(Pipeline $pipeline, int $limit = 100): iterable;

    /**
     * @return iterable<PipelineExecutionStatistic>
     */
    public function findByPipelineBetween(
        Pipeline $pipeline,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): iterable;
}
