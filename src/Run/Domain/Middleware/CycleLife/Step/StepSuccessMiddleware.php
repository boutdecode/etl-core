<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;

final readonly class StepSuccessMiddleware implements Middleware
{
    public function __construct(
        private ?Logger $logger = null
    ) {
    }

    public function process(Context $context, callable $next): Context
    {
        $stepName = $context->getCurrentStep()?->getCode() ?? 'unknown';

        $resultSet = $context->getResultSet();
        $errorMessage = is_array($resultSet) ? ($resultSet['error'] ?? false) : false;
        if (is_string($errorMessage) && $errorMessage !== '') {
            $this->logger?->error(
                "Step '{$stepName}' completed with errors",
                $context,
                new \Exception($errorMessage)
            );

            /** @var Context $result */
            $result = $next($context);

            return $result;
        }

        $this->logger?->info("Step '{$stepName}' completed successfully", $context);

        /** @var Context $result */
        $result = $next($context);

        return $result;
    }
}
