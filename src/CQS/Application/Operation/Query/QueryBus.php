<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query;

interface QueryBus
{
    public function ask(Query $query): mixed;
}
