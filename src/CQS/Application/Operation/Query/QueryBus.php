<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query;

interface QueryBus
{
    /**
     * @param array<\Symfony\Component\Messenger\Stamp\StampInterface> $stamps
     */
    public function ask(Query $query, array $stamps = []): mixed;
}
