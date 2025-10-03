<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Factory;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\PipelineHistory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory;

interface PipelineHistoryFactory
{
    /**
     * @param array<StepHistory> $stepHistories
     */
    public function create(
        Pipeline $pipeline,
        PipelineHistoryStatusEnum $status,
        array $stepHistories,
        mixed $input,
        mixed $result,
    ): PipelineHistory;
}
