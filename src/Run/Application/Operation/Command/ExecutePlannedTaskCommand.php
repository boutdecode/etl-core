<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command;

use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\SyncCommand;

final readonly class ExecutePlannedTaskCommand implements SyncCommand
{
    public function __construct(
        public string $plannedTaskId,
    ) {
    }
}
