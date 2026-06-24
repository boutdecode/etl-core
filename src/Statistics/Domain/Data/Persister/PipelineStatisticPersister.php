<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineStatistic;

interface PipelineStatisticPersister
{
    public function create(PipelineStatistic $pipelineStatistic): PipelineStatistic;

    public function save(PipelineStatistic $pipelineStatistic): PipelineStatistic;
}
