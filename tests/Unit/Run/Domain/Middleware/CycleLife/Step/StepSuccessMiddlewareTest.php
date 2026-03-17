<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepSuccessMiddleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StepSuccessMiddlewareTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldLogSuccessWhenNoError(): void
    {
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('my_step');
        $step->method('getName')->willReturn('my_step');

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('my_step'),
                $this->isInstanceOf(Context::class)
            );
        $logger->expects($this->never())->method('error');

        $middleware = new StepSuccessMiddleware($logger);
        $context = new Context('input');
        $context->setCurrentStep($step);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldLogErrorWhenResultSetHasError(): void
    {
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('my_step');
        $step->method('getName')->willReturn('my_step');

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('my_step'),
                $this->isInstanceOf(Context::class),
                $this->isInstanceOf(\Exception::class)
            );
        $logger->expects($this->never())->method('info');

        $middleware = new StepSuccessMiddleware($logger);
        $context = new Context('input');
        $context->setCurrentStep($step);
        $context->setResult('my_step', [
            'error' => 'something failed',
        ]);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextWhenNoError(): void
    {
        $middleware = new StepSuccessMiddleware();
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
    public function processShouldCallNextWhenError(): void
    {
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('my_step');
        $step->method('getName')->willReturn('my_step');

        $middleware = new StepSuccessMiddleware();
        $context = new Context('input');
        $context->setCurrentStep($step);
        $context->setResult('my_step', [
            'error' => 'error message',
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
    public function processShouldTreatEmptyStringErrorAsNoError(): void
    {
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('my_step');
        $step->method('getName')->willReturn('my_step');

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('error');
        $logger->expects($this->once())->method('info');

        $middleware = new StepSuccessMiddleware($logger);
        $context = new Context('input');
        $context->setCurrentStep($step);
        // An empty string error is treated as no error
        $context->setResult('my_step', [
            'error' => '',
        ]);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldLogWithUnknownWhenNoCurrentStep(): void
    {
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('unknown'),
                $this->isInstanceOf(Context::class)
            );

        $middleware = new StepSuccessMiddleware($logger);
        $context = new Context('input');

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotLogWhenNoLogger(): void
    {
        $middleware = new StepSuccessMiddleware();
        $context = new Context('input');

        // No exception should be thrown
        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldTreatNonArrayResultSetAsNoError(): void
    {
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('my_step');
        $step->method('getName')->willReturn('my_step');

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('error');
        $logger->expects($this->once())->method('info');

        $middleware = new StepSuccessMiddleware($logger);
        $context = new Context('input');
        $context->setCurrentStep($step);
        $context->setResult('my_step', 'a_plain_string_result');

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }
}
