<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepFailureMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepMiddlewareChain;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepProcessMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepStartMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepSuccessMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\StepMiddlewareChain as StepMiddlewareChainInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StepMiddlewareChainTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructShouldImplementStepMiddlewareChainInterface(): void
    {
        $chain = new StepMiddlewareChain();

        $this->assertInstanceOf(StepMiddlewareChainInterface::class, $chain);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithoutMiddlewaresShouldAddFourDefaultMiddlewares(): void
    {
        $chain = new StepMiddlewareChain();

        $reflection = new \ReflectionClass($chain);
        $parent = $reflection->getParentClass();
        $this->assertNotFalse($parent);

        $prop = $parent->getProperty('middlewares');
        $prop->setAccessible(true);
        $middlewares = $prop->getValue($chain);

        $this->assertCount(4, $middlewares);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function defaultMiddlewaresShouldContainExpectedTypes(): void
    {
        $chain = new StepMiddlewareChain();

        $reflection = new \ReflectionClass($chain);
        $parent = $reflection->getParentClass();
        $this->assertNotFalse($parent);

        $prop = $parent->getProperty('middlewares');
        $prop->setAccessible(true);
        $middlewares = $prop->getValue($chain);

        $this->assertInstanceOf(StepStartMiddleware::class, $middlewares[0]);
        $this->assertInstanceOf(StepFailureMiddleware::class, $middlewares[1]);
        $this->assertInstanceOf(StepProcessMiddleware::class, $middlewares[2]);
        $this->assertInstanceOf(StepSuccessMiddleware::class, $middlewares[3]);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithCustomMiddlewaresShouldUseThemInsteadOfDefaults(): void
    {
        $middleware = $this->createMock(Middleware::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(fn (Context $ctx, callable $next) => $next($ctx));

        $chain = new StepMiddlewareChain([$middleware]);
        $context = new Context('input');

        $result = $chain->run($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithCustomMiddlewaresShouldNotUseDefaultMiddlewares(): void
    {
        $middleware = $this->createMock(Middleware::class);
        $middleware->method('process')
            ->willReturnCallback(fn (Context $ctx, callable $next) => $next($ctx));

        $chain = new StepMiddlewareChain([$middleware]);

        $reflection = new \ReflectionClass($chain);
        $parent = $reflection->getParentClass();
        $this->assertNotFalse($parent);

        $prop = $parent->getProperty('middlewares');
        $prop->setAccessible(true);
        $middlewares = $prop->getValue($chain);

        $this->assertCount(1, $middlewares);
        $this->assertSame($middleware, $middlewares[0]);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldExecuteChainAndReturnContext(): void
    {
        $middleware = $this->createMock(Middleware::class);
        $middleware->method('process')
            ->willReturnCallback(fn (Context $ctx, callable $next) => $next($ctx));

        $chain = new StepMiddlewareChain([$middleware]);
        $context = new Context('input');

        $result = $chain->run($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
    }
}
