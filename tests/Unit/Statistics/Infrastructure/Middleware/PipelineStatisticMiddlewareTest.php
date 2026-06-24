<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Statistics\Infrastructure\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister\PipelineStatisticPersister;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\PipelineStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory\PipelineStatisticFactory;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\PipelineStatistic;
use BoutDeCode\ETLCoreBundle\Statistics\Infrastructure\Middleware\PipelineStatisticMiddleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineStatisticMiddlewareTest extends TestCase
{
    private PipelineStatisticProvider $provider;

    private PipelineStatisticFactory $factory;

    private PipelineStatisticPersister $persister;

    private PipelineStatisticMiddleware $middleware;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(PipelineStatisticProvider::class);
        $this->factory = $this->createMock(PipelineStatisticFactory::class);
        $this->persister = $this->createMock(PipelineStatisticPersister::class);
        $this->middleware = new PipelineStatisticMiddleware(
            $this->provider,
            $this->factory,
            $this->persister,
        );
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
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCreateStatisticWhenNoneExists(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStartedAt')->willReturn(new \DateTimeImmutable('-5 seconds'));

        $statistic = $this->createMock(PipelineStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $this->provider->expects($this->once())
            ->method('findByPipeline')
            ->with($pipeline)
            ->willReturn(null);

        $this->factory->expects($this->once())
            ->method('create')
            ->with($pipeline)
            ->willReturn($statistic);

        $statistic->expects($this->once())->method('recordSuccess');

        $this->persister->expects($this->once())
            ->method('create')
            ->with($statistic)
            ->willReturn($statistic);

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldUpdateExistingStatistic(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStartedAt')->willReturn(new \DateTimeImmutable('-3 seconds'));

        $statistic = $this->createMock(PipelineStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $this->provider->expects($this->once())
            ->method('findByPipeline')
            ->with($pipeline)
            ->willReturn($statistic);

        $this->factory->expects($this->never())->method('create');

        $statistic->expects($this->once())->method('recordSuccess');

        $this->persister->expects($this->once())
            ->method('save')
            ->with($statistic)
            ->willReturn($statistic);

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldRecordFailureWhenContextHasErrors(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStartedAt')->willReturn(new \DateTimeImmutable('-2 seconds'));

        $statistic = $this->createMock(PipelineStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);
        $context->setResult('step_one', [
            'error' => 'Something went wrong',
        ]);

        $this->provider->method('findByPipeline')->willReturn($statistic);

        $statistic->expects($this->once())->method('recordFailure');
        $statistic->expects($this->never())->method('recordSuccess');

        $this->persister->method('save')->willReturn($statistic);

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldHandleNullStartedAt(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStartedAt')->willReturn(null);

        $statistic = $this->createMock(PipelineStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $this->provider->method('findByPipeline')->willReturn($statistic);
        $statistic->expects($this->once())->method('recordSuccess')->with(0.0);
        $this->persister->method('save')->willReturn($statistic);

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAndReturnResult(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStartedAt')->willReturn(new \DateTimeImmutable());

        $statistic = $this->createMock(PipelineStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $this->provider->method('findByPipeline')->willReturn($statistic);
        $statistic->method('recordSuccess');
        $this->persister->method('save')->willReturn($statistic);

        $nextCalled = false;
        $result = $this->middleware->process($context, function ($ctx) use (&$nextCalled) {
            $nextCalled = true;

            return $ctx->setResult('final', 'done');
        });

        $this->assertTrue($nextCalled);
        $this->assertSame('done', $result->getResult());
    }
}
