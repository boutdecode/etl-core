<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;

final readonly class PipelineSuccessMiddleware implements Middleware
{
    public function __construct(
        private PipelineWorkflow $pipelineWorkflow,
        private ?Logger $logger = null
    ) {
    }

    public function process(Context $context, callable $next): Context
    {
        $pipeline = $context->getPipeline();

        if ($context->getErrors()) {
            $error = new \Exception(implode(', ', $context->getErrors()));

            $this->logger?->error('Pipeline completed with errors', $context, $error, [
                'errors' => $context->getErrors(),
            ]);

            if ($pipeline !== null) {
                $this->pipelineWorkflow->fail($pipeline, $error);
            }

            /** @var Context $result */
            $result = $next($context);
            return $result;
        }

        $this->logger?->info('Pipeline completed successfully', $context);

        if ($pipeline !== null) {
            $this->pipelineWorkflow->complete($pipeline);
        }

        /** @var Context $result */
        $result = $next($context);
        return $result;
    }
}
