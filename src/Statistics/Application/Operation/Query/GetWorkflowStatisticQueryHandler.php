<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Application\Operation\Query;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\WorkflowProvider;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\QueryHandler;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\WorkflowStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowStatistic;

final readonly class GetWorkflowStatisticQueryHandler implements QueryHandler
{
    public function __construct(
        private WorkflowProvider $workflowProvider,
        private WorkflowStatisticProvider $workflowStatisticProvider,
    ) {
    }

    public function __invoke(GetWorkflowStatisticQuery $query): ?WorkflowStatistic
    {
        $workflow = $this->workflowProvider->findWorkflowByIdentifier($query->workflowId);

        if ($workflow === null) {
            return null;
        }

        return $this->workflowStatisticProvider->findByWorkflow($workflow);
    }
}
