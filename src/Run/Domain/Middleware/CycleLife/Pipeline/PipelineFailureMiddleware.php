<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;

final readonly class PipelineFailureMiddleware implements Middleware
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
            $this->logger?->error("Pipeline CRITICAL: {$exception->getMessage()}", $context, $exception);
        }

        return $context;
    }
}
