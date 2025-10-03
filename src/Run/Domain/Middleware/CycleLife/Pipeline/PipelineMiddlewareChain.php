<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\AbstractMiddlewareChain;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepMiddlewareChain;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\PipelineMiddlewareChain as PipelineMiddlewareChainInterface;

final class PipelineMiddlewareChain extends AbstractMiddlewareChain implements PipelineMiddlewareChainInterface
{
    public function __construct(iterable $middlewares = [])
    {
        if (empty($middlewares)) {
            $middlewares = [
                new PipelineProcessMiddleware(new StepMiddlewareChain()),
            ];
        }

        parent::__construct($middlewares);
    }
}
