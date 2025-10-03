<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;

final readonly class StepFailureMiddleware implements Middleware
{
    public function __construct(
        private ?Logger $logger = null
    ) {
    }

    public function process(Context $context, callable $next): Context
    {
        try {
            /** @var Context $result */
            $result = $next($context);
            return $result;
        } catch (\Throwable $exception) {
            $stepName = $context->getCurrentStep()?->getCode() ?? 'unknown';

            $this->logger?->error("Step '{$stepName}' CRITICAL: {$exception->getMessage()}", $context, $exception);
        }

        return $context;
    }
}
