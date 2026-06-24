<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineStatistic;

interface PipelineStatisticProvider
{
    public function findByPipeline(Pipeline $pipeline): ?PipelineStatistic;

    /**
     * @return iterable<PipelineStatistic>
     */
    public function findAll(): iterable;
}
