<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Model;

use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;

abstract class AbstractStepHistory implements StepHistory
{
    protected StepHistoryStatusEnum $status;

    protected \DateTimeImmutable $createdAt;

    protected mixed $input;

    protected mixed $result;

    public function getStatus(): StepHistoryStatusEnum
    {
        return $this->status;
    }

    public function getInput(): mixed
    {
        return $this->input;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
