<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;

interface PipelinePersister
{
    public function create(Pipeline $pipeline): Pipeline;

    public function save(Pipeline $pipeline): Pipeline;
}
