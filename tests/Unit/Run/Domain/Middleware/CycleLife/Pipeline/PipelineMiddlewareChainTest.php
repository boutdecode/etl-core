<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline\PipelineMiddlewareChain;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline\PipelineProcessMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\PipelineMiddlewareChain as PipelineMiddlewareChainInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineMiddlewareChainTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructShouldImplementPipelineMiddlewareChainInterface(): void
    {
        $chain = new PipelineMiddlewareChain();

        $this->assertInstanceOf(PipelineMiddlewareChainInterface::class, $chain);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithoutMiddlewaresShouldAddDefaultPipelineProcessMiddleware(): void
    {
        $chain = new PipelineMiddlewareChain();

        // The default chain contains PipelineProcessMiddleware, which throws if no pipeline.
        // We verify by checking that the chain still runs (doesn't throw on an empty pipeline-less context).
        $context = new Context('input');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pipeline cannot be null');

        $chain->run($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithCustomMiddlewaresShouldUseThemInsteadOfDefaults(): void
    {
        $middleware = $this->createMock(Middleware::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(fn (Context $ctx, callable $next) => $next($ctx));

        $chain = new PipelineMiddlewareChain([$middleware]);
        $context = new Context('input');

        $result = $chain->run($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithCustomMiddlewaresShouldNotUseDefaultProcessMiddleware(): void
    {
        $middleware = $this->createMock(Middleware::class);
        $middleware->method('process')
            ->willReturnCallback(fn (Context $ctx, callable $next) => $next($ctx));

        // Custom middleware — should NOT trigger the RuntimeException from PipelineProcessMiddleware
        $chain = new PipelineMiddlewareChain([$middleware]);
        $context = new Context('input');

        // No pipeline set — should NOT throw because PipelineProcessMiddleware is not used
        $result = $chain->run($context, fn (Context $ctx) => $ctx);

        $this->assertSame($context, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function defaultChainShouldContainPipelineProcessMiddleware(): void
    {
        $chain = new PipelineMiddlewareChain();

        // Verify via reflection that the default middleware is PipelineProcessMiddleware
        $reflection = new \ReflectionClass($chain);
        $parent = $reflection->getParentClass();
        $this->assertNotFalse($parent);

        $prop = $parent->getProperty('middlewares');
        $prop->setAccessible(true);
        $middlewares = $prop->getValue($chain);

        $this->assertCount(1, $middlewares);
        $this->assertInstanceOf(PipelineProcessMiddleware::class, $middlewares[0]);
    }
}
