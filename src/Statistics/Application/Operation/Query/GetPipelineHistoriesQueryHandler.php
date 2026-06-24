<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\QueryHandler;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Provider\PipelineHistoryProvider;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\PipelineHistory;

final readonly class GetPipelineHistoriesQueryHandler implements QueryHandler
{
    public function __construct(
        private PipelineProvider $pipelineProvider,
        private PipelineHistoryProvider $pipelineHistoryProvider,
    ) {
    }

    /**
     * @return iterable<PipelineHistory>
     */
    public function __invoke(GetPipelineHistoriesQuery $query): iterable
    {
        $pipeline = $this->pipelineProvider->findPipelineByIdentifier($query->pipelineId);

        if ($pipeline === null) {
            return [];
        }

        return $this->pipelineHistoryProvider->findByPipelineBetween($pipeline, $query->from, $query->to);
    }
}
