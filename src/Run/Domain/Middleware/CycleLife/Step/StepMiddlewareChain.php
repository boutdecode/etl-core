<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\AbstractMiddlewareChain;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\StepMiddlewareChain as StepMiddlewareChainInterface;

final class StepMiddlewareChain extends AbstractMiddlewareChain implements StepMiddlewareChainInterface
{
    public function __construct(iterable $middlewares = [])
    {
        if (empty($middlewares)) {
            $middlewares = [
                new StepStartMiddleware(),
                new StepFailureMiddleware(),
                new StepProcessMiddleware(),
                new StepSuccessMiddleware(),
            ];
        }

        parent::__construct($middlewares);
    }
}
