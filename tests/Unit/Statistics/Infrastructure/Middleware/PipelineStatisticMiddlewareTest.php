<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Statistics\Infrastructure\Middleware;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister\WorkflowExecutionStatisticPersister;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Persister\WorkflowStatisticPersister;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Data\Provider\WorkflowStatisticProvider;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory\WorkflowExecutionStatisticFactory;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Factory\WorkflowStatisticFactory;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowExecutionStatistic;
use BoutDeCode\ETLCoreBundle\Statistics\Domain\Model\WorkflowStatistic;
use BoutDeCode\ETLCoreBundle\Statistics\Infrastructure\Middleware\PipelineStatisticMiddleware;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineStatisticMiddlewareTest extends TestCase
{
    private WorkflowStatisticProvider $provider;

    private WorkflowStatisticFactory $factory;

    private WorkflowStatisticPersister $persister;

    private WorkflowExecutionStatisticFactory $executionFactory;

    private WorkflowExecutionStatisticPersister $executionPersister;

    private PipelineStatisticMiddleware $middleware;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(WorkflowStatisticProvider::class);
        $this->factory = $this->createMock(WorkflowStatisticFactory::class);
        $this->persister = $this->createMock(WorkflowStatisticPersister::class);
        $this->executionFactory = $this->createMock(WorkflowExecutionStatisticFactory::class);
        $this->executionPersister = $this->createMock(WorkflowExecutionStatisticPersister::class);

        $executionStatistic = $this->createMock(WorkflowExecutionStatistic::class);
        $this->executionFactory->method('create')->willReturn($executionStatistic);
        $this->executionPersister->method('create')->willReturnArgument(0);

        $this->middleware = new PipelineStatisticMiddleware(
            $this->provider,
            $this->factory,
            $this->persister,
            $this->executionFactory,
            $this->executionPersister,
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
        $pipeline = $this->createPipelineMock(new \DateTimeImmutable('-5 seconds'));
        $statistic = $this->createMock(WorkflowStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $this->provider->expects($this->once())
            ->method('findByWorkflow')
            ->willReturn(null);

        $this->factory->expects($this->once())
            ->method('create')
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
        $pipeline = $this->createPipelineMock(new \DateTimeImmutable('-3 seconds'));
        $statistic = $this->createMock(WorkflowStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $this->provider->expects($this->once())
            ->method('findByWorkflow')
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
        $pipeline = $this->createPipelineMock(new \DateTimeImmutable('-2 seconds'));
        $statistic = $this->createMock(WorkflowStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);
        $context->setResult('step_one', [
            'error' => 'Something went wrong',
        ]);

        $this->provider->method('findByWorkflow')->willReturn($statistic);

        $statistic->expects($this->once())->method('recordFailure');
        $statistic->expects($this->never())->method('recordSuccess');

        $this->persister->method('save')->willReturn($statistic);

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldHandleNullStartedAt(): void
    {
        $pipeline = $this->createPipelineMock(null);
        $statistic = $this->createMock(WorkflowStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $this->provider->method('findByWorkflow')->willReturn($statistic);
        $statistic->expects($this->once())->method('recordSuccess')->with(0);
        $this->persister->method('save')->willReturn($statistic);

        $this->middleware->process($context, fn ($ctx) => $ctx);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAndReturnResult(): void
    {
        $pipeline = $this->createPipelineMock(new \DateTimeImmutable());
        $statistic = $this->createMock(WorkflowStatistic::class);

        $context = new Context('input');
        $context->setPipeline($pipeline);

        $this->provider->method('findByWorkflow')->willReturn($statistic);
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

    private function createPipelineMock(?\DateTimeImmutable $startedAt = null): Pipeline
    {
        $workflow = $this->createMock(Workflow::class);
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getWorkflow')->willReturn($workflow);
        $pipeline->method('getStartedAt')->willReturn($startedAt);

        return $pipeline;
    }
}
