<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Notifications\Infrastructure\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationMessage;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationProvider;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Resolver\NotificationProviderResolver;
use BoutDeCode\ETLCoreBundle\Notifications\Infrastructure\Middleware\NotificationMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NotificationMiddlewareTest extends TestCase
{
    private NotificationProviderResolver $resolver;

    private Logger $logger;

    private NotificationMiddleware $middleware;

    protected function setUp(): void
    {
        $this->resolver = $this->createMock(NotificationProviderResolver::class);
        $this->logger = $this->createMock(Logger::class);

        $this->middleware = new NotificationMiddleware($this->resolver, $this->logger);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextWhenNoPipeline(): void
    {
        $context = new Context('input');
        $nextCalled = false;

        $result = $this->middleware->process($context, function ($ctx) use (&$nextCalled) {
            $nextCalled = true;

            return $ctx;
        });

        $this->assertTrue($nextCalled);
        $this->assertSame($context, $result);
    }

    #[Test]
    public function processShouldNotNotifyWhenOnSuccessIsDisabled(): void
    {
        $pipeline = $this->createPipelineMock(notifyOnSuccess: false);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $this->resolver->expects($this->never())->method('list');
        $this->resolver->expects($this->never())->method('resolve');

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    public function processShouldNotifyAllProvidersOnSuccessWhenNoneSpecified(): void
    {
        $pipeline = $this->createPipelineMock(notifyOnSuccess: true);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $provider = $this->createMock(NotificationProvider::class);
        $provider->expects($this->once())
            ->method('notify')
            ->with($this->isInstanceOf(NotificationMessage::class));

        $this->resolver->expects($this->once())->method('list')->willReturn([$provider]);
        $this->resolver->expects($this->never())->method('resolve');

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    public function processShouldNotifyOnlyConfiguredProviders(): void
    {
        $pipeline = $this->createPipelineMock(notifyOnFailure: true, providers: ['email']);

        $context = new Context('input');
        $context->setPipeline($pipeline);
        $context->setResult('step_one', [
            'error' => 'boom',
        ]);

        $provider = $this->createMock(NotificationProvider::class);
        $provider->expects($this->once())->method('notify');

        $this->resolver->expects($this->never())->method('list');
        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with('email')
            ->willReturn($provider);

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    public function processShouldNotNotifyOnFailureWhenNotConfigured(): void
    {
        $pipeline = $this->createPipelineMock(notifyOnSuccess: true);

        $context = new Context('input');
        $context->setPipeline($pipeline);
        $context->setResult('step_one', [
            'error' => 'boom',
        ]);

        $this->resolver->expects($this->never())->method('list');
        $this->resolver->expects($this->never())->method('resolve');

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    public function processShouldLogAndContinueWhenProviderThrows(): void
    {
        $pipeline = $this->createPipelineMock(notifyOnSuccess: true);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $provider = $this->createMock(NotificationProvider::class);
        $provider->method('getCode')->willReturn('email');
        $provider->method('notify')->willThrowException(new \RuntimeException('smtp down'));

        $this->resolver->method('list')->willReturn([$provider]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->isType('string'),
                $context,
                $this->isInstanceOf(\RuntimeException::class),
                [
                    'provider' => 'email',
                ],
            );

        $nextCalled = false;
        $result = $this->middleware->process($context, function ($ctx) use (&$nextCalled) {
            $nextCalled = true;

            return $ctx;
        });

        $this->assertTrue($nextCalled);
        $this->assertSame($context, $result);
    }

    /**
     * @param string[]|null $providers
     */
    private function createPipelineMock(
        bool $notifyOnSuccess = false,
        bool $notifyOnFailure = false,
        ?array $providers = null,
    ): Pipeline {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isNotifyOnSuccess')->willReturn($notifyOnSuccess);
        $workflow->method('isNotifyOnFailure')->willReturn($notifyOnFailure);
        $workflow->method('getNotificationProviders')->willReturn($providers);

        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getWorkflow')->willReturn($workflow);

        return $pipeline;
    }
}
