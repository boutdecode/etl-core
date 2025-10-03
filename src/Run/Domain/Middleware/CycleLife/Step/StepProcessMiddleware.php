<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;

final readonly class StepProcessMiddleware implements Middleware
{
    public function __construct(
        private ?Logger $logger = null
    ) {
    }

    public function process(Context $context, callable $next): Context
    {
        try {
            $currentStep = $context->getCurrentStep();
            if ($currentStep instanceof ExecutableStep) {
                $currentStep->process($context);
            }
        } catch (\Throwable $exception) {
            $stepName = $context->getCurrentStep()?->getName() ?? 'unknown';

            $context->setResult($stepName, [
                'error' => $exception->getMessage() . ' file: ' . $exception->getFile() . ' line: ' . $exception->getLine(),
            ]);

            $this->logger?->error("Step '{$stepName}' failed: {$exception->getMessage()}", $context, $exception);
        }

        /** @var Context $result */
        $result = $next($context);

        return $result;
    }
}
