<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\AbstractMiddlewareChain;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\Middleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractMiddlewareChainTest extends TestCase
{
    private TestMiddlewareChain $middlewareChain;

    protected function setUp(): void
    {
        $this->middlewareChain = new TestMiddlewareChain();
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithoutMiddlewaresShouldCreateEmptyChain(): void
    {
        $chain = new TestMiddlewareChain();
        $context = new Context('test input');

        $result = $chain->run($context, function ($ctx) {
            return $ctx->setResult('final', 'processed');
        });

        $this->assertInstanceOf(Context::class, $result);
        $this->assertSame('processed', $result->getResult());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function constructWithMiddlewaresShouldAddAllMiddlewares(): void
    {
        $middleware1 = $this->createMockMiddleware('middleware1');
        $middleware2 = $this->createMockMiddleware('middleware2');

        $chain = new TestMiddlewareChain([$middleware1, $middleware2]);
        $context = new Context('test input');

        $result = $chain->run($context, function ($ctx) {
            return $ctx;
        });

        $this->assertInstanceOf(Context::class, $result);
        // With reverse order execution, middleware1 (first added) executes last and overwrites the result
        $this->assertSame('middleware1', $result->getResult());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function addMiddlewareShouldAddMiddlewareToChain(): void
    {
        $middleware = $this->createMockMiddleware('test');

        $result = $this->middlewareChain->addMiddleware($middleware);

        $this->assertSame($this->middlewareChain, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldExecuteMiddlewaresInReverseOrder(): void
    {
        $middleware1 = $this->createMockMiddleware('first');
        $middleware2 = $this->createMockMiddleware('second');

        $this->middlewareChain->addMiddleware($middleware1);
        $this->middlewareChain->addMiddleware($middleware2);

        $context = new Context('test input');

        $result = $this->middlewareChain->run($context, function ($ctx) {
            return $ctx->setResult('final', 'processed');
        });

        // With reverse order execution, first middleware (first added) executes last and overwrites the result
        $this->assertSame('first', $result->getResult());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runShouldCallFinalCallableAfterAllMiddlewares(): void
    {
        $middleware = $this->createMockMiddleware('middleware');
        $this->middlewareChain->addMiddleware($middleware);

        $context = new Context('test input');
        $finalCallableCalled = false;

        $result = $this->middlewareChain->run($context, function ($ctx) use (&$finalCallableCalled) {
            $finalCallableCalled = true;
            return $ctx->setResult('final', 'processed_by_callable');
        });

        $this->assertTrue($finalCallableCalled);
        // Middleware should have processed after the final callable
        $this->assertSame('middleware', $result->getResult());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runWithMultipleMiddlewaresShouldCreateCorrectChain(): void
    {
        // Create middlewares that append to a result
        $middleware1 = $this->createAppendingMiddleware('A');
        $middleware2 = $this->createAppendingMiddleware('B');
        $middleware3 = $this->createAppendingMiddleware('C');

        $this->middlewareChain->addMiddleware($middleware1);
        $this->middlewareChain->addMiddleware($middleware2);
        $this->middlewareChain->addMiddleware($middleware3);

        $context = new Context([]);

        $result = $this->middlewareChain->run($context, function ($ctx) {
            $data = $ctx->getInput();
            $data[] = 'FINAL';
            return $ctx->setResult('chain', $data);
        });

        // Should be A, B, C, FINAL (execution order: C calls next -> B calls next -> A calls next -> FINAL)
        // Each middleware adds its value before calling next, so A, B, C, FINAL is the correct order
        $expected = ['A', 'B', 'C', 'FINAL'];
        $this->assertSame($expected, $result->getResult());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function interfaceShouldBeImplemented(): void
    {
        $this->assertInstanceOf(
            \BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\MiddlewareChain::class,
            $this->middlewareChain
        );
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function runWithEmptyChainShouldJustCallNext(): void
    {
        $context = new Context('input');

        $result = $this->middlewareChain->run($context, function ($ctx) {
            return $ctx->setResult('empty_chain', 'direct_result');
        });

        $this->assertSame('direct_result', $result->getResult());
    }

    private function createMockMiddleware(string $identifier): Middleware
    {
        $middleware = $this->createMock(Middleware::class);
        $middleware->method('process')
            ->willReturnCallback(function (Context $context, callable $next) use ($identifier) {
                $result = $next($context);
                return $result->setResult('test', $identifier);
            });

        return $middleware;
    }

    private function createAppendingMiddleware(string $value): Middleware
    {
        $middleware = $this->createMock(Middleware::class);
        $middleware->method('process')
            ->willReturnCallback(function (Context $context, callable $next) use ($value) {
                $data = $context->getInput();
                if (! is_array($data)) {
                    $data = [];
                }
                $data[] = $value;
                $context = new Context($data, [], [], []);
                return $next($context);
            });

        return $middleware;
    }
}

// Test class for testing AbstractMiddlewareChain
class TestMiddlewareChain extends AbstractMiddlewareChain
{
}
