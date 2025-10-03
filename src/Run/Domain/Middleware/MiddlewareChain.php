<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

interface MiddlewareChain
{
    public function addMiddleware(Middleware $middleware): self;

    public function run(Context $context, callable $next): Context;
}
