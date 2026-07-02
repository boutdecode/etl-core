<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
final readonly class PurgeOldPipelines
{
}
