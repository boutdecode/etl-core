<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline\PipelineStartMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineStartMiddlewareTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAndReturnResult(): void
    {
        $workflow = $this->createMock(PipelineWorkflow::class);
        $middleware = new PipelineStartMiddleware($workflow);
        $context = new Context('input');

        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertSame($context, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotStartWorkflowWhenNoPipeline(): void
    {
        $workflow = $this->createMock(PipelineWorkflow::class);
        $workflow->expects($this->never())->method('start');

        $middleware = new PipelineStartMiddleware($workflow);
        $context = new Context('input');

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldStartWorkflowWhenPipelineIsPresent(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $workflow = $this->createMock(PipelineWorkflow::class);
        $workflow->expects($this->once())->method('start')->with($pipeline);

        $middleware = new PipelineStartMiddleware($workflow);
        $context = new Context('input');
        $context->setPipeline($pipeline);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldLogInfoWhenLoggerIsProvided(): void
    {
        $workflow = $this->createMock(PipelineWorkflow::class);
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Pipeline started', $this->isInstanceOf(Context::class));

        $middleware = new PipelineStartMiddleware($workflow, $logger);
        $context = new Context('input');

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotLogWhenNoLogger(): void
    {
        $workflow = $this->createMock(PipelineWorkflow::class);
        $middleware = new PipelineStartMiddleware($workflow);
        $context = new Context('input');

        // No exception should be thrown
        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAfterWorkflowStart(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $workflow = $this->createMock(PipelineWorkflow::class);

        $middleware = new PipelineStartMiddleware($workflow);
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
}
