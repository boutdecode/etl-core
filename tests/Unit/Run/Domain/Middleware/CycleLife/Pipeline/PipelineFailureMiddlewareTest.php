<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline\PipelineFailureMiddleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineFailureMiddlewareTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAndReturnResult(): void
    {
        $middleware = new PipelineFailureMiddleware();
        $context = new Context('input');

        $result = $middleware->process($context, fn (Context $ctx) => $ctx);

        $this->assertSame($context, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldReturnNextResult(): void
    {
        $middleware = new PipelineFailureMiddleware();
        $context = new Context('input');
        $other = new Context('other');

        $result = $middleware->process($context, fn () => $other);

        $this->assertSame($other, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCatchThrowableAndReturnContext(): void
    {
        $middleware = new PipelineFailureMiddleware();
        $context = new Context('input');

        $result = $middleware->process($context, function (): never {
            throw new \RuntimeException('Something went wrong');
        });

        $this->assertSame($context, $result);
    }

    #[Test]
    public function processShouldLogErrorWhenExceptionIsThrownAndLoggerIsProvided(): void
    {
        $logger = $this->createMock(Logger::class);
        $middleware = new PipelineFailureMiddleware($logger);
        $context = new Context('input');
        $exception = new \RuntimeException('boom');

        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('boom'),
                $context,
                $exception
            );

        $middleware->process($context, function () use ($exception): never {
            throw $exception;
        });
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldNotLogWhenNoExceptionAndNoLogger(): void
    {
        $middleware = new PipelineFailureMiddleware();
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
    public function processShouldNotLogWhenNoExceptionButLoggerIsProvided(): void
    {
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('error');

        $middleware = new PipelineFailureMiddleware($logger);
        $context = new Context('input');

        $middleware->process($context, fn (Context $ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCatchErrorThrowable(): void
    {
        $middleware = new PipelineFailureMiddleware();
        $context = new Context('input');

        $result = $middleware->process($context, function (): never {
            throw new \Error('Fatal error');
        });

        $this->assertSame($context, $result);
    }
}
