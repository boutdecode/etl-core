<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineStatistic;

interface PipelineStatisticFactory
{
    public function create(Pipeline $pipeline): PipelineStatistic;
}
