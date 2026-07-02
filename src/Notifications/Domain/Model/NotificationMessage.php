<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Notifications\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;

final readonly class NotificationMessage
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        public Workflow $workflow,
        public Pipeline $pipeline,
        public PipelineHistoryStatusEnum $status,
        public array $errors = [],
        public mixed $result = null,
    ) {
    }
}
