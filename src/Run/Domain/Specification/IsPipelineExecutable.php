<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Specification;

use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;

class IsPipelineExecutable implements IsPipelineExecutableSpecification
{
    public function isSatisfiedBy(Pipeline $pipeline): bool
    {
        return $pipeline->getStatus() === PipelineStatus::PENDING;
    }
}
