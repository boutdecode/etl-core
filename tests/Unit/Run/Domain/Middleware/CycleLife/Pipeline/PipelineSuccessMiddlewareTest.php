<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline\PipelineSuccessMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineSuccessMiddlewareTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallCompleteWhenNoErrors(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $workflow = $this->createMock(PipelineWorkflow::class);
        $workflow->expects($this->once())->method('complete')->with($pipeline);
        $workflow->expects($this->never())->method('fail');

        $middleware = new PipelineSuccessMiddleware($workflow);
        $context = new Context('input');
        $context->setPipeline($pipeline);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallFailWhenContextHasErrors(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $workflow = $this->createMock(PipelineWorkflow::class);
        $workflow->expects($this->once())->method('fail')->with($pipeline, $this->isInstanceOf(\Exception::class));
        $workflow->expects($this->never())->method('complete');

        $middleware = new PipelineSuccessMiddleware($workflow);
        $context = new Context('input');
        $context->setPipeline($pipeline);
        $context->setResult('step1', [
            'error' => 'Something went wrong',
        ]);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldLogSuccessWhenNoErrors(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $workflow = $this->createMock(PipelineWorkflow::class);
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Pipeline completed successfully', $this->isInstanceOf(Context::class));
        $logger->expects($this->never())->method('error');

        $middleware = new PipelineSuccessMiddleware($workflow, $logger);
        $context = new Context('input');
        $context->setPipeline($pipeline);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldLogErrorWhenContextHasErrors(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $workflow = $this->createMock(PipelineWorkflow::class);
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'Pipeline completed with errors',
                $this->isInstanceOf(Context::class),
                $this->isInstanceOf(\Exception::class)
            );
        $logger->expects($this->never())->method('info');

        $middleware = new PipelineSuccessMiddleware($workflow, $logger);
        $context = new Context('input');
        $context->setPipeline($pipeline);
        $context->setResult('step1', [
            'error' => 'Something went wrong',
        ]);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotCallWorkflowWhenNoPipeline(): void
    {
        $workflow = $this->createMock(PipelineWorkflow::class);
        $workflow->expects($this->never())->method('complete');
        $workflow->expects($this->never())->method('fail');

        $middleware = new PipelineSuccessMiddleware($workflow);
        $context = new Context('input');

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextWhenNoErrors(): void
    {
        $workflow = $this->createMock(PipelineWorkflow::class);
        $middleware = new PipelineSuccessMiddleware($workflow);
        $context = new Context('input');

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
    public function processShouldCallNextWhenErrors(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $workflow = $this->createMock(PipelineWorkflow::class);
        $middleware = new PipelineSuccessMiddleware($workflow);
        $context = new Context('input');
        $context->setPipeline($pipeline);
        $context->setResult('step1', [
            'error' => 'error msg',
        ]);

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
    public function processShouldBuildExceptionMessageFromAllErrors(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $workflow = $this->createMock(PipelineWorkflow::class);
        $logger = $this->createMock(Logger::class);

        $capturedThrowable = new \Exception('placeholder');
        $workflow->method('fail')
            ->willReturnCallback(function (Pipeline $p, \Throwable $e) use (&$capturedThrowable): void {
                $capturedThrowable = $e;
            });

        $middleware = new PipelineSuccessMiddleware($workflow, $logger);
        $context = new Context('input');
        $context->setPipeline($pipeline);
        $context->setResult('step1', [
            'error' => 'Error A',
        ]);
        $context->setResult('step2', [
            'error' => 'Error B',
        ]);

        $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertStringContainsString('Error A', $capturedThrowable->getMessage());
        $this->assertStringContainsString('Error B', $capturedThrowable->getMessage());
    }
}
