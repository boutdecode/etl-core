<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;

abstract class AbstractMiddlewareChain implements MiddlewareChain
{
    /**
     * @var Middleware[]
     */
    private array $middlewares = [];

    /**
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        iterable $middlewares = []
    ) {
        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }
    }

    public function addMiddleware(Middleware $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function run(Context $context, callable $next): Context
    {
        $chain = array_reduce(
            array_reverse($this->middlewares),
            function (callable $next, Middleware $middleware): callable {
                return function (Context $context) use ($next, $middleware): Context {
                    return $middleware->process($context, $next);
                };
            },
            $next
        );

        /** @var Context $result */
        $result = $chain($context);

        return $result;
    }
}
