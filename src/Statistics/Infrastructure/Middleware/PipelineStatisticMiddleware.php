<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Statistics\Infrastructure\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister\PipelineStatisticPersister;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\PipelineStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory\PipelineStatisticFactory;

final readonly class PipelineStatisticMiddleware implements Middleware
{
    public function __construct(
        private PipelineStatisticProvider $pipelineStatisticProvider,
        private PipelineStatisticFactory $pipelineStatisticFactory,
        private PipelineStatisticPersister $pipelineStatisticPersister,
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

        $startedAt = $pipeline->getStartedAt();
        $finishedAt = new \DateTimeImmutable();
        $durationSeconds = $startedAt !== null
            ? (float) ($finishedAt->getTimestamp() - $startedAt->getTimestamp())
            : 0.0;

        $statistic = $this->pipelineStatisticProvider->findByPipeline($pipeline);
        $isNew = $statistic === null;

        if ($isNew) {
            $statistic = $this->pipelineStatisticFactory->create($pipeline);
        }

        if ($context->getErrors()) {
            $statistic->recordFailure($durationSeconds);
        } else {
            $statistic->recordSuccess($durationSeconds);
        }

        if ($isNew) {
            $this->pipelineStatisticPersister->create($statistic);
        } else {
            $this->pipelineStatisticPersister->save($statistic);
        }

        /** @var Context $result */
        $result = $next($context);

        return $result;
    }
}
