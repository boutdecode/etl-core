<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister;

use BoutDeCode\ETLCoreBundle\Run\Domain\Model\PipelineHistory;

interface PipelineHistoryPersister
{
    public function create(PipelineHistory $pipelineHistory): PipelineHistory;
}
