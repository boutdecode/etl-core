<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

interface Middleware
{
    public function process(Context $context, callable $next): Context;
}
