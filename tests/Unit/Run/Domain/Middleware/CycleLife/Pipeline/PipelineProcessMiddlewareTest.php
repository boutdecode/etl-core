<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline\PipelineProcessMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\StepMiddlewareChain;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineProcessMiddlewareTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldThrowWhenNoPipeline(): void
    {
        $chain = $this->createMock(StepMiddlewareChain::class);
        $middleware = new PipelineProcessMiddleware($chain);
        $context = new Context('input');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pipeline cannot be null');

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldRunStepMiddlewareChainForEachRunnableStep(): void
    {
        $step1 = $this->createMock(Step::class);
        $step2 = $this->createMock(Step::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getRunnableSteps')->willReturn([$step1, $step2]);

        $chain = $this->createMock(StepMiddlewareChain::class);
        $chain->expects($this->exactly(2))
            ->method('run')
            ->willReturnCallback(fn (Context $ctx, callable $next) => $next($ctx));

        $middleware = new PipelineProcessMiddleware($chain);
        $context = new Context('input');
        $context->setPipeline($pipeline);

        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldSetCurrentStepForEachRunnableStep(): void
    {
        $step1 = $this->createMock(Step::class);
        $step2 = $this->createMock(Step::class);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getRunnableSteps')->willReturn([$step1, $step2]);

        $capturedSteps = [];

        $chain = $this->createMock(StepMiddlewareChain::class);
        $chain->method('run')
            ->willReturnCallback(function (Context $ctx, callable $next) use (&$capturedSteps): Context {
                $capturedSteps[] = $ctx->getCurrentStep();
                return $next($ctx);
            });

        $middleware = new PipelineProcessMiddleware($chain);
        $context = new Context('input');
        $context->setPipeline($pipeline);

        $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertSame([$step1, $step2], $capturedSteps);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAfterAllSteps(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getRunnableSteps')->willReturn([]);

        $chain = $this->createMock(StepMiddlewareChain::class);

        $middleware = new PipelineProcessMiddleware($chain);
        $context = new Context('input');
        $context->setPipeline($pipeline);

        $nextCalled = false;
        $result = $middleware->process($context, function (Context $ctx) use (&$nextCalled): Context {
            $nextCalled = true;
            return $ctx;
        });

        $this->assertTrue($nextCalled);
        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldHandleEmptyRunnableSteps(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getRunnableSteps')->willReturn([]);

        $chain = $this->createMock(StepMiddlewareChain::class);
        $chain->expects($this->never())->method('run');

        $middleware = new PipelineProcessMiddleware($chain);
        $context = new Context('input');
        $context->setPipeline($pipeline);

        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertSame($context, $result);
    }
}
