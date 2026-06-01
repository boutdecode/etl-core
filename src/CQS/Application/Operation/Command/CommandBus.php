<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command;

interface CommandBus
{
    /**
     * @param array<\Symfony\Component\Messenger\Stamp\StampInterface> $stamps
     */
    public function dispatch(Command $command, array $stamps = []): mixed;
}
