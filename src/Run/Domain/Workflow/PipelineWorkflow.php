<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Workflow;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;

interface PipelineWorkflow
{
    public function start(Pipeline $pipeline): void;

    public function complete(Pipeline $pipeline): void;

    public function fail(Pipeline $pipeline, \Throwable $throwable): void;

    public function reset(Pipeline $pipeline): void;

    public function restart(Pipeline $pipeline): void;
}
