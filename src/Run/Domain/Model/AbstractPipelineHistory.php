<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

abstract class AbstractPipelineHistory implements PipelineHistory
{
    protected Pipeline $pipeline;

    protected PipelineHistoryStatusEnum $status;

    protected \DateTimeImmutable $createdAt;

    protected mixed $input;

    protected mixed $result;

    /**
     * @var StepHistory[]
     */
    protected iterable $stepHistories;

    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }

    public function getStatus(): PipelineHistoryStatusEnum
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getInput(): mixed
    {
        return $this->input;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getStepHistories(): iterable
    {
        return $this->stepHistories;
    }
}
