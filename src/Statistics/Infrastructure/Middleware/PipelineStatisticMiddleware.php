<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Infrastructure\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister\WorkflowExecutionStatisticPersister;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister\WorkflowStatisticPersister;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\WorkflowStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory\WorkflowExecutionStatisticFactory;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory\WorkflowStatisticFactory;

final readonly class PipelineStatisticMiddleware implements Middleware
{
    public function __construct(
        private WorkflowStatisticProvider $workflowStatisticProvider,
        private WorkflowStatisticFactory $workflowStatisticFactory,
        private WorkflowStatisticPersister $workflowStatisticPersister,
        private WorkflowExecutionStatisticFactory $workflowExecutionStatisticFactory,
        private WorkflowExecutionStatisticPersister $workflowExecutionStatisticPersister,
    ) {
    }

    public function process(Context $context, callable $next): Context
    {
        $pipeline = $context->getPipeline();

        if ($pipeline === null) {
            /** @var Context $result */
            $result = $next($context);

            return $result;
        }

        $workflow = $pipeline->getWorkflow();
        $startedAt = $pipeline->getStartedAt();
        $finishedAt = new \DateTimeImmutable();
        $durationMs = $startedAt !== null
            ? (int) round(((float) $finishedAt->format('U.u') - (float) $startedAt->format('U.u')) * 1000)
            : 0;

        $hasErrors = (bool) $context->getErrors();

        $statistic = $this->workflowStatisticProvider->findByWorkflow($workflow);
        $isNew = $statistic === null;

        if ($isNew) {
            $statistic = $this->workflowStatisticFactory->create($workflow);
        }

        if ($hasErrors) {
            $statistic->recordFailure($durationMs);
        } else {
            $statistic->recordSuccess($durationMs);
        }

        if ($isNew) {
            $this->workflowStatisticPersister->create($statistic);
        } else {
            $this->workflowStatisticPersister->save($statistic);
        }

        $execution = $this->workflowExecutionStatisticFactory->create(
            $workflow,
            $hasErrors ? PipelineHistoryStatusEnum::FAILED : PipelineHistoryStatusEnum::COMPLETED,
            $startedAt ?? new \DateTimeImmutable(),
            $finishedAt,
        );
        $this->workflowExecutionStatisticPersister->create($execution);

        /** @var Context $result */
        $result = $next($context);

        return $result;
    }
}
