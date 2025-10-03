<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Middleware\CycleLife\Step;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\StepHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Factory\StepHistoryFactory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Middleware\CycleLife\Step\StepHistoryMiddleware;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StepHistoryMiddlewareTest extends TestCase
{
    private StepHistoryMiddleware $middleware;

    private StepHistoryPersister $stepHistoryPersister;

    private StepHistoryFactory $stepHistoryFactory;

    protected function setUp(): void
    {
        $this->stepHistoryPersister = $this->createMock(StepHistoryPersister::class);
        $this->stepHistoryFactory = $this->createMock(StepHistoryFactory::class);
        $this->middleware = new StepHistoryMiddleware(
            $this->stepHistoryPersister,
            $this->stepHistoryFactory
        );
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCallNextWhenNoCurrentStep(): void
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
    public function processShouldCallNextWhenNoOriginalStep(): void
    {
        $context = new Context('test input');
        $currentStep = $this->createMock(Step::class);
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStepFromRunnableStep')->willReturn(null);

        $context->setCurrentStep($currentStep);
        $context->setPipeline($pipeline);

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
    public function processShouldCreateAndPersistStepHistoryWhenCompletedSuccessfully(): void
    {
        $context = new Context('test input');
        $currentStep = $this->createMock(Step::class);
        $currentStep->method('getCode')->willReturn('step1');
        $currentStep->method('getName')->willReturn('step1');
        $originalStep = $this->createMock(Step::class);
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStepFromRunnableStep')->willReturn($originalStep);
        $stepHistory = $this->createMock(StepHistory::class);

        $context->setCurrentStep($currentStep);
        $context->setPipeline($pipeline);

        $stepHistoryFactory = $this->createMock(StepHistoryFactory::class);
        $stepHistoryPersister = $this->createMock(StepHistoryPersister::class);

        $stepHistoryFactory->expects($this->once())
            ->method('create')
            ->with(
                $originalStep,
                StepHistoryStatusEnum::COMPLETED,
                $this->isNull(), // inputSet should be null when no current step
                $this->isNull()  // resultSet should be null when no current step
            )
            ->willReturn($stepHistory);

        $stepHistoryPersister->expects($this->once())
            ->method('create')
            ->with($stepHistory)
            ->willReturn($stepHistory);

        $middleware = new StepHistoryMiddleware($stepHistoryPersister, $stepHistoryFactory);

        $nextCalled = false;
        $result = $middleware->process($context, function ($ctx) use (&$nextCalled) {
            $nextCalled = true;
            return $ctx;
        });

        $this->assertTrue($nextCalled);
        $this->assertInstanceOf(Context::class, $result);

        // Verify that step history is added to configuration
        $stepHistories = $result->getConfigurationValue(StepHistoryMiddleware::STEP_HISTORIES_CONFIG_KEY, []);
        $this->assertContains($stepHistory, $stepHistories);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldCreateFailedStepHistoryWhenErrorDetected(): void
    {
        $context = new Context('test input');
        $currentStep = $this->createMock(Step::class);
        $currentStep->method('getCode')->willReturn('step1');
        $currentStep->method('getName')->willReturn('step1');
        $originalStep = $this->createMock(Step::class);
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStepFromRunnableStep')->willReturn($originalStep);
        $stepHistory = $this->createMock(StepHistory::class);

        $context->setCurrentStep($currentStep);
        $context->setPipeline($pipeline);
        $context->setResult('step1', [
            'error' => 'Something went wrong',
        ]);

        $stepHistoryFactory = $this->createMock(StepHistoryFactory::class);
        $stepHistoryPersister = $this->createMock(StepHistoryPersister::class);

        $stepHistoryFactory->expects($this->once())
            ->method('create')
            ->with(
                $originalStep,
                StepHistoryStatusEnum::FAILED,
                $this->anything(),
                $this->callback(function ($resultSet) {
                    return is_array($resultSet) && isset($resultSet['error']);
                })
            )
            ->willReturn($stepHistory);

        $stepHistoryPersister->expects($this->once())
            ->method('create')
            ->with($stepHistory)
            ->willReturn($stepHistory);

        $middleware = new StepHistoryMiddleware($stepHistoryPersister, $stepHistoryFactory);

        $middleware->process($context, function ($ctx) {
            return $ctx;
        });
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldAppendToExistingStepHistories(): void
    {
        $context = new Context('test input');
        $currentStep = $this->createMock(Step::class);
        $currentStep->method('getCode')->willReturn('step1');
        $currentStep->method('getName')->willReturn('step1');
        $originalStep = $this->createMock(Step::class);
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStepFromRunnableStep')->willReturn($originalStep);
        $existingStepHistory = $this->createMock(StepHistory::class);
        $newStepHistory = $this->createMock(StepHistory::class);

        $context->setCurrentStep($currentStep);
        $context->setPipeline($pipeline);
        $context->setConfigurationValue(StepHistoryMiddleware::STEP_HISTORIES_CONFIG_KEY, [$existingStepHistory]);

        $stepHistoryFactory = $this->createMock(StepHistoryFactory::class);
        $stepHistoryPersister = $this->createMock(StepHistoryPersister::class);

        $stepHistoryFactory->method('create')->willReturn($newStepHistory);
        $stepHistoryPersister->method('create')->willReturn($newStepHistory);

        $middleware = new StepHistoryMiddleware($stepHistoryPersister, $stepHistoryFactory);

        $result = $middleware->process($context, function ($ctx) {
            return $ctx;
        });

        $stepHistories = $result->getConfigurationValue(StepHistoryMiddleware::STEP_HISTORIES_CONFIG_KEY, []);
        $this->assertCount(2, $stepHistories);
        $this->assertContains($existingStepHistory, $stepHistories);
        $this->assertContains($newStepHistory, $stepHistories);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function processShouldUseInputSetAndResultSetFromContext(): void
    {
        $context = new Context('test input');
        $currentStep = $this->createMock(Step::class);
        $currentStep->method('getCode')->willReturn('step1');
        $currentStep->method('getName')->willReturn('step1');
        $originalStep = $this->createMock(Step::class);
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStepFromRunnableStep')->willReturn($originalStep);
        $stepHistory = $this->createMock(StepHistory::class);

        $context->setCurrentStep($currentStep);
        $context->setPipeline($pipeline);
        $context->setResult('step1', [
            'processed' => 'data',
        ]);

        $expectedInput = $context->getInputSet();
        $expectedResult = $context->getResultSet();

        $stepHistoryFactory = $this->createMock(StepHistoryFactory::class);
        $stepHistoryPersister = $this->createMock(StepHistoryPersister::class);

        $stepHistoryFactory->expects($this->once())
            ->method('create')
            ->with(
                $originalStep,
                StepHistoryStatusEnum::COMPLETED,
                $expectedInput,
                $expectedResult
            )
            ->willReturn($stepHistory);

        $stepHistoryPersister->method('create')->willReturn($stepHistory);

        $middleware = new StepHistoryMiddleware($stepHistoryPersister, $stepHistoryFactory);

        $middleware->process($context, function ($ctx) {
            return $ctx;
        });
    }
}
