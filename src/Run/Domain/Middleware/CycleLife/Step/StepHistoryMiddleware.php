<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\StepHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Factory\StepHistoryFactory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;

final readonly class StepHistoryMiddleware implements Middleware
{
    public const STEP_HISTORIES_CONFIG_KEY = 'step_histories';

    public function __construct(
        private StepHistoryPersister $stepHistoryPersister,
        private StepHistoryFactory $stepHistoryFactory,
    ) {
    }

    public function process(Context $context, callable $next): Context
    {
        $currentStep = $context->getCurrentStep();
        if ($currentStep === null) {
            /** @var Context $result */
            $result = $next($context);
            return $result;
        }

        $orignalStep = $context->getPipeline()?->getStepFromRunnableStep($currentStep);
        if ($orignalStep === null) {
            /** @var Context $result */
            $result = $next($context);
            return $result;
        }

        $resultSet = $context->getResultSet();
        $hasError = is_array($resultSet) ? ($resultSet['error'] ?? false) : false; // @TODO Not ideal, refactor needed

        $stepHistory = $this->stepHistoryFactory->create(
            $orignalStep,
            $hasError ? StepHistoryStatusEnum::FAILED : StepHistoryStatusEnum::COMPLETED,
            $context->getInputSet(),
            $context->getResultSet(),
        );

        $this->stepHistoryPersister->create($stepHistory);

        $existingHistories = $context->getConfigurationValue(self::STEP_HISTORIES_CONFIG_KEY, []);
        $context->setConfigurationValue(self::STEP_HISTORIES_CONFIG_KEY, array_merge(
            is_array($existingHistories) ? $existingHistories : [],
            [$stepHistory],
        ));

        /** @var Context $result */
        $result = $next($context);
        return $result;
    }
}
