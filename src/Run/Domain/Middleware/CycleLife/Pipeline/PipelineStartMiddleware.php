<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;

final readonly class PipelineStartMiddleware implements Middleware
{
    public function __construct(
        private PipelineWorkflow $pipelineWorkflow,
        private ?Logger $logger = null
    ) {
    }

    public function process(Context $context, callable $next): Context
    {
        $this->logger?->info('Pipeline started', $context);

        $pipeline = $context->getPipeline();
        if ($pipeline !== null) {
            $this->pipelineWorkflow->start($pipeline);
        }

        /** @var Context $result */
        $result = $next($context);
        return $result;
    }
}
