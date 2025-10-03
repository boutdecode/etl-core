<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\StepMiddlewareChain;

final readonly class PipelineProcessMiddleware implements Middleware
{
    public function __construct(
        private StepMiddlewareChain $middlewareChain,
    ) {
    }

    public function process(Context $context, callable $next): Context
    {
        $pipeline = $context->getPipeline();
        if ($pipeline === null) {
            throw new \RuntimeException('Pipeline cannot be null');
        }

        foreach ($pipeline->getRunnableSteps() as $step) {
            $context->setCurrentStep($step);
            $this->middlewareChain->run($context, function (Context $context): Context {
                return $context;
            });
        }

        /** @var Context $result */
        $result = $next($context);
        return $result;
    }
}
