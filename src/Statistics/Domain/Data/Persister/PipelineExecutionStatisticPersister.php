<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineExecutionStatistic;

interface PipelineExecutionStatisticPersister
{
    public function create(PipelineExecutionStatistic $pipelineExecutionStatistic): PipelineExecutionStatistic;
}
