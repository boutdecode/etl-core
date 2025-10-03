<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\PipelineHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Factory\PipelineHistoryFactory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepHistoryMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;

final readonly class PipelineHistoryMiddleware implements Middleware
{
    public function __construct(
        private PipelineHistoryPersister $pipelineHistoryPersister,
        private PipelineHistoryFactory $pipelineHistoryFactory
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

        /** @var array<\BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory> $stepHistories */
        $stepHistories = $context->getConfigurationValue(StepHistoryMiddleware::STEP_HISTORIES_CONFIG_KEY, []);

        $pipelineHistory = $this->pipelineHistoryFactory->create(
            $pipeline,
            $context->getErrors() ? PipelineHistoryStatusEnum::FAILED : PipelineHistoryStatusEnum::COMPLETED,
            $stepHistories,
            $context->getInitialInput(),
            $context->getResult()
        );

        $this->pipelineHistoryPersister->create($pipelineHistory);

        /** @var Context $result */
        $result = $next($context);
        return $result;
    }
}
