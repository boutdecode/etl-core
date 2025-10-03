<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

interface PipelineHistory
{
    public function getPipeline(): Pipeline;

    public function getStatus(): PipelineHistoryStatusEnum;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getInput(): mixed;

    public function getResult(): mixed;

    /**
     * @return StepHistory[]
     */
    public function getStepHistories(): iterable;
}
