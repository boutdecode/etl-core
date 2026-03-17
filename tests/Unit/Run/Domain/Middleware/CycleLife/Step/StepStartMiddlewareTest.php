<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepStartMiddleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StepStartMiddlewareTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAndReturnResult(): void
    {
        $middleware = new StepStartMiddleware();
        $context = new Context('input');

        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertSame($context, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldLogInfoWithStepCodeWhenCurrentStepIsPresent(): void
    {
        $step = $this->createMock(Step::class);
        $step->method('getCode')->willReturn('my_step');

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('my_step'),
                $this->isInstanceOf(Context::class)
            );

        $middleware = new StepStartMiddleware($logger);
        $context = new Context('input');
        $context->setCurrentStep($step);

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    public function processShouldLogInfoWithUnknownWhenNoCurrentStep(): void
    {
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('unknown'),
                $this->isInstanceOf(Context::class)
            );

        $middleware = new StepStartMiddleware($logger);
        $context = new Context('input');

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotLogWhenNoLogger(): void
    {
        $middleware = new StepStartMiddleware();
        $context = new Context('input');

        // No exception should be thrown
        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAfterLogging(): void
    {
        $middleware = new StepStartMiddleware();
        $context = new Context('input');

        $nextCalled = false;
        $result = $middleware->process($context, function (Context $ctx) use (&$nextCalled): Context {
            $nextCalled = true;
            return $ctx;
        });

        $this->assertTrue($nextCalled);
        $this->assertInstanceOf(Context::class, $result);
    }
}
