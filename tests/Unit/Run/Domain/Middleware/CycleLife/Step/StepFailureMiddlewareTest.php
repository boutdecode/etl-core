<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepFailureMiddleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StepFailureMiddlewareTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAndReturnResult(): void
    {
        $middleware = new StepFailureMiddleware();
        $context = new Context('input');

        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertSame($context, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCatchThrowableAndReturnContext(): void
    {
        $middleware = new StepFailureMiddleware();
        $context = new Context('input');

        $result = $middleware->process($context, function (): never {
            throw new \RuntimeException('step failed');
        });

        $this->assertSame($context, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldLogErrorWithStepCodeWhenExceptionThrown(): void
    {
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('my_step');

        $logger = $this->createMock(Logger::class);
        $exception = new \RuntimeException('boom');

        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('my_step'),
                $this->isInstanceOf(Context::class),
                $exception
            );

        $middleware = new StepFailureMiddleware($logger);
        $context = new Context('input');
        $context->setCurrentStep($step);

        $middleware->process($context, function () use ($exception): never {
            throw $exception;
        });
    }

    #[Test]
    public function processShouldLogErrorWithUnknownStepCodeWhenNoCurrentStep(): void
    {
        $logger = $this->createMock(Logger::class);
        $exception = new \RuntimeException('boom');

        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('unknown'),
                $this->isInstanceOf(Context::class),
                $exception
            );

        $middleware = new StepFailureMiddleware($logger);
        $context = new Context('input');

        $middleware->process($context, function () use ($exception): never {
            throw $exception;
        });
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotLogWhenNoExceptionAndNoLogger(): void
    {
        $middleware = new StepFailureMiddleware();
        $context = new Context('input');
        $called = false;

        $middleware->process($context, function (Context $ctx) use (&$called): Context {
            $called = true;
            return $ctx;
        });

        $this->assertTrue($called);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCatchErrorThrowable(): void
    {
        $middleware = new StepFailureMiddleware();
        $context = new Context('input');

        $result = $middleware->process($context, function (): never {
            throw new \Error('Fatal error');
        });

        $this->assertSame($context, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotLogWhenNoExceptionButLoggerIsProvided(): void
    {
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('error');

        $middleware = new StepFailureMiddleware($logger);
        $context = new Context('input');

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }
}
