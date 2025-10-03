<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Pipeline;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\PipelineHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Factory\PipelineHistoryFactory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Pipeline\PipelineHistoryMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepHistoryMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\PipelineHistory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineHistoryMiddlewareTest extends TestCase
{
    private PipelineHistoryMiddleware $middleware;

    private PipelineHistoryPersister $pipelineHistoryPersister;

    private PipelineHistoryFactory $pipelineHistoryFactory;

    protected function setUp(): void
    {
        $this->pipelineHistoryPersister = $this->createMock(PipelineHistoryPersister::class);
        $this->pipelineHistoryFactory = $this->createMock(PipelineHistoryFactory::class);
        $this->middleware = new PipelineHistoryMiddleware(
            $this->pipelineHistoryPersister,
            $this->pipelineHistoryFactory
        );
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextWhenNoPipeline(): void
    {
        $context = new Context('test input');
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
    public function processShouldCreateAndPersistPipelineHistoryWhenPipelineExists(): void
    {
        $initialInput = [
            'initial' => 'data',
        ];
        $finalResult = [
            'final' => 'result',
        ];
        $stepHistories = [/* mock step histories */];

        $context = new Context($initialInput);
        $pipeline = $this->createMock(Pipeline::class);
        $pipelineHistory = $this->createMock(PipelineHistory::class);

        $context->setPipeline($pipeline);
        $context->setResult('final_step', $finalResult);
        $context->setConfigurationValue(StepHistoryMiddleware::STEP_HISTORIES_CONFIG_KEY, $stepHistories);

        $pipelineHistoryFactory = $this->createMock(PipelineHistoryFactory::class);
        $pipelineHistoryPersister = $this->createMock(PipelineHistoryPersister::class);

        $pipelineHistoryFactory->expects($this->once())
            ->method('create')
            ->with(
                $pipeline,
                PipelineHistoryStatusEnum::COMPLETED,
                $stepHistories,
                $initialInput,
                $finalResult
            )
            ->willReturn($pipelineHistory);

        $pipelineHistoryPersister->expects($this->once())
            ->method('create')
            ->with($pipelineHistory)
            ->willReturn($pipelineHistory);

        $middleware = new PipelineHistoryMiddleware($pipelineHistoryPersister, $pipelineHistoryFactory);

        $nextCalled = false;
        $result = $middleware->process($context, function ($ctx) use (&$nextCalled) {
            $nextCalled = true;
            return $ctx;
        });

        $this->assertTrue($nextCalled);
        $this->assertInstanceOf(Context::class, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldUseEmptyStepHistoriesWhenNotConfigured(): void
    {
        $initialInput = [
            'test' => 'input',
        ];
        $context = new Context($initialInput);
        $pipeline = $this->createMock(Pipeline::class);
        $pipelineHistory = $this->createMock(PipelineHistory::class);

        $context->setPipeline($pipeline);

        $pipelineHistoryFactory = $this->createMock(PipelineHistoryFactory::class);
        $pipelineHistoryPersister = $this->createMock(PipelineHistoryPersister::class);

        $pipelineHistoryFactory->expects($this->once())
            ->method('create')
            ->with(
                $pipeline,
                PipelineHistoryStatusEnum::COMPLETED,
                [], // Should default to empty array when no step histories
                $initialInput,
                $this->anything()
            )
            ->willReturn($pipelineHistory);

        $pipelineHistoryPersister->method('create')->willReturn($pipelineHistory);

        $middleware = new PipelineHistoryMiddleware($pipelineHistoryPersister, $pipelineHistoryFactory);

        $middleware->process($context, function ($ctx) {
            return $ctx;
        });
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldUseInitialInputAndCurrentResult(): void
    {
        $initialInput = [
            'original' => 'data',
        ];
        $processedResult = [
            'processed' => 'result',
        ];

        $context = new Context($initialInput);
        $pipeline = $this->createMock(Pipeline::class);
        $pipelineHistory = $this->createMock(PipelineHistory::class);

        $context->setPipeline($pipeline);
        $context->setResult('some_step', $processedResult);

        $pipelineHistoryFactory = $this->createMock(PipelineHistoryFactory::class);
        $pipelineHistoryPersister = $this->createMock(PipelineHistoryPersister::class);

        $pipelineHistoryFactory->expects($this->once())
            ->method('create')
            ->with(
                $pipeline,
                PipelineHistoryStatusEnum::COMPLETED,
                [],
                $initialInput, // Should use getInitialInput()
                $processedResult // Should use getResult()
            )
            ->willReturn($pipelineHistory);

        $pipelineHistoryPersister->method('create')->willReturn($pipelineHistory);

        $middleware = new PipelineHistoryMiddleware($pipelineHistoryPersister, $pipelineHistoryFactory);

        $middleware->process($context, function ($ctx) {
            return $ctx;
        });
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextAndReturnResult(): void
    {
        $context = new Context('test input');
        $pipeline = $this->createMock(Pipeline::class);
        $pipelineHistory = $this->createMock(PipelineHistory::class);

        $context->setPipeline($pipeline);

        $pipelineHistoryFactory = $this->createMock(PipelineHistoryFactory::class);
        $pipelineHistoryPersister = $this->createMock(PipelineHistoryPersister::class);

        $pipelineHistoryFactory->method('create')->willReturn($pipelineHistory);
        $pipelineHistoryPersister->method('create')->willReturn($pipelineHistory);

        $middleware = new PipelineHistoryMiddleware($pipelineHistoryPersister, $pipelineHistoryFactory);

        $result = $middleware->process($context, function ($ctx) {
            return $ctx->setResult('test', 'modified');
        });

        $this->assertInstanceOf(Context::class, $result);
        $this->assertSame('modified', $result->getResult());
    }
}
