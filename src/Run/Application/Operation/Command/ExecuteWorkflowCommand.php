<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command;

use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\AsyncCommand;

final readonly class ExecuteWorkflowCommand implements AsyncCommand
{
    public function __construct(
        public string $pipelineId,
    ) {
    }
}
