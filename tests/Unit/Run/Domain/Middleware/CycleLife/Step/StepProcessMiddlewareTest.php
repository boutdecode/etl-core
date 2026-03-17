<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepProcessMiddleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StepProcessMiddlewareTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAndReturnResult(): void
    {
        $middleware = new StepProcessMiddleware();
        $context = new Context('input');

        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertSame($context, $result);
    }

    #[Test]
    public function processShouldCallProcessOnExecutableStep(): void
    {
        $step = $this->createMock(ExecutableStep::class);
        $step->expects($this->once())->method('process');

        $middleware = new StepProcessMiddleware();
        $context = new Context('input');
        $context->setCurrentStep($step);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotCallProcessWhenCurrentStepIsNotExecutableStep(): void
    {
        $step = $this->createMock(Step::class);
        // Step::class does not have process() as an ExecutableStep — no call expected

        $middleware = new StepProcessMiddleware();
        $context = new Context('input');
        $context->setCurrentStep($step);

        // Should not throw
        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotCallProcessWhenNoCurrentStep(): void
    {
        $middleware = new StepProcessMiddleware();
        $context = new Context('input');

        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCatchExceptionAndStoreInResultSet(): void
    {
        $step = $this->createMock(ExecutableStep::class);
        $step->method('getName')->willReturn('failing_step');
        $step->method('process')->willThrowException(new \RuntimeException('step error', 0));

        $middleware = new StepProcessMiddleware();
        $context = new Context('input');
        $context->setCurrentStep($step);

        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
        // The error is stored under the step name key
        $errorResult = $result->getResultByKey('failing_step');
        $this->assertIsArray($errorResult);
        $this->assertArrayHasKey('error', $errorResult);
        $this->assertStringContainsString('step error', $errorResult['error']);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldLogErrorWhenExceptionThrownAndLoggerProvided(): void
    {
        $step = $this->createMock(ExecutableStep::class);
        $step->method('getName')->willReturn('my_step');
        $step->method('process')->willThrowException(new \RuntimeException('boom'));

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('my_step'),
                $this->isInstanceOf(Context::class),
                $this->isInstanceOf(\RuntimeException::class)
            );

        $middleware = new StepProcessMiddleware($logger);
        $context = new Context('input');
        $context->setCurrentStep($step);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextEvenAfterException(): void
    {
        $step = $this->createMock(ExecutableStep::class);
        $step->method('getName')->willReturn('step');
        $step->method('process')->willThrowException(new \RuntimeException('error'));

        $middleware = new StepProcessMiddleware();
        $context = new Context('input');
        $context->setCurrentStep($step);

        $nextCalled = false;
        $middleware->process($context, function (Context $ctx) use (&$nextCalled): Context {
            $nextCalled = true;
            return $ctx;
        });

        $this->assertTrue($nextCalled);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldStoreFileAndLineInErrorResult(): void
    {
        $step = $this->createMock(ExecutableStep::class);
        $step->method('getName')->willReturn('step');
        $step->method('process')->willThrowException(new \RuntimeException('oops'));

        $middleware = new StepProcessMiddleware();
        $context = new Context('input');
        $context->setCurrentStep($step);

        $middleware->process($context, fn (Context $ctx) => $ctx);

        $errorResult = $context->getResultByKey('step');
        $this->assertIsArray($errorResult);
        $this->assertStringContainsString('file:', $errorResult['error']);
        $this->assertStringContainsString('line:', $errorResult['error']);
    }
}
