<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query;

use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\SyncQuery;

final readonly class GetPipelineStatisticQuery implements SyncQuery
{
    public function __construct(
        public string $pipelineId,
    ) {
    }
}
