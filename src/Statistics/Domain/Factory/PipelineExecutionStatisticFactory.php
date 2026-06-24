<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineExecutionStatistic;

interface PipelineExecutionStatisticFactory
{
    public function create(
        Pipeline $pipeline,
        PipelineHistoryStatusEnum $status,
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $finishedAt,
    ): PipelineExecutionStatistic;
}
