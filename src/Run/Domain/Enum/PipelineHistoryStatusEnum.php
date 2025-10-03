<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Enum;

enum PipelineHistoryStatusEnum: string
{
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
