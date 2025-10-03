<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Specification;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;

interface IsPipelineExecutableSpecification
{
    public function isSatisfiedBy(Pipeline $pipeline): bool;
}
