<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\QueryHandler;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\PipelineStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineStatistic;

final readonly class GetPipelineStatisticQueryHandler implements QueryHandler
{
    public function __construct(
        private PipelineProvider $pipelineProvider,
        private PipelineStatisticProvider $pipelineStatisticProvider,
    ) {
    }

    public function __invoke(GetPipelineStatisticQuery $query): ?PipelineStatistic
    {
        $pipeline = $this->pipelineProvider->findPipelineByIdentifier($query->pipelineId);

        if ($pipeline === null) {
            return null;
        }

        return $this->pipelineStatisticProvider->findByPipeline($pipeline);
    }
}
