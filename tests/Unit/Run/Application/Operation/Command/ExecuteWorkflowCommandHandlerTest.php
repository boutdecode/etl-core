<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Application\Operation\Command;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecuteWorkflowCommand;
use BoutDeCode\ETLCoreBundle\Run\Application\Operation\Command\ExecuteWorkflowCommandHandler;
use BoutDeCode\ETLCoreBundle\Run\Domain\Runner\PipelineRunner;
use BoutDeCode\ETLCoreBundle\Run\Domain\Specification\IsPipelineExecutableSpecification;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

class ExecuteWorkflowCommandHandlerTest extends TestCase
{
    private PipelineProvider $pipelineProvider;

    private PipelineRunner $pipelineRunner;

    private IsPipelineExecutableSpecification $isPipelineExecutableSpecification;

    private ExecuteWorkflowCommandHandler $handler;

    protected function setUp(): void
    {
        $this->pipelineProvider = $this->createMock(PipelineProvider::class);
        $this->pipelineRunner = $this->createMock(PipelineRunner::class);
        $this->isPipelineExecutableSpecification = $this->createMock(IsPipelineExecutableSpecification::class);

        $this->handler = new ExecuteWorkflowCommandHandler(
            $this->pipelineProvider,
            $this->pipelineRunner,
            $this->isPipelineExecutableSpecification
        );
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itExecutesPipelineWhenPipelineIsExecutable(): void
    {
        $pipelineId = 'test-pipeline-123';
        $command = new ExecuteWorkflowCommand($pipelineId);
        $pipeline = $this->createMock(Pipeline::class);
        $expectedContext = new Context('result_data');

        $this->pipelineProvider
            ->expects($this->once())
            ->method('findPipelineByIdentifier')
            ->with($pipelineId)
            ->willReturn($pipeline);

        $this->isPipelineExecutableSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($pipeline)
            ->willReturn(true);

        $this->pipelineRunner
            ->expects($this->once())
            ->method('run')
            ->with($pipeline)
            ->willReturn($expectedContext);

        $result = $this->handler->__invoke($command);

        $this->assertSame($expectedContext, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itReturnsNoExecutionContextWhenPipelineIsNotExecutable(): void
    {
        $pipelineId = 'inactive-pipeline-456';
        $command = new ExecuteWorkflowCommand($pipelineId);
        $pipeline = $this->createMock(Pipeline::class);

        $this->pipelineProvider
            ->expects($this->once())
            ->method('findPipelineByIdentifier')
            ->with($pipelineId)
            ->willReturn($pipeline);

        $this->isPipelineExecutableSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($pipeline)
            ->willReturn(false);

        $this->pipelineRunner
            ->expects($this->never())
            ->method('run');

        $result = $this->handler->__invoke($command);

        $this->assertInstanceOf(Context::class, $result);
        $this->assertSame('', $result->getInput());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itThrowsExceptionWhenPipelineNotFound(): void
    {
        $pipelineId = 'non-existent-pipeline';
        $command = new ExecuteWorkflowCommand($pipelineId);

        $this->pipelineProvider
            ->expects($this->once())
            ->method('findPipelineByIdentifier')
            ->with($pipelineId)
            ->willReturn(null);

        $this->isPipelineExecutableSpecification
            ->expects($this->never())
            ->method('isSatisfiedBy');

        $this->pipelineRunner
            ->expects($this->never())
            ->method('run');

        $this->expectException(InvalidArgumentException::class);

        $this->handler->__invoke($command);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itThrowsExceptionWhenPipelineProviderReturnsInvalidType(): void
    {
        $pipelineId = 'invalid-pipeline';
        $command = new ExecuteWorkflowCommand($pipelineId);

        // Create a mock that bypasses type checking by using a different approach
        $pipelineProvider = $this->createStub(PipelineProvider::class);
        $pipelineProvider->method('findPipelineByIdentifier')
            ->willReturnCallback(function () {
                // This simulates returning an invalid type that would fail the type hint
                return new \stdClass(); // This is not a Pipeline object
            });

        $handler = new ExecuteWorkflowCommandHandler(
            $pipelineProvider,
            $this->pipelineRunner,
            $this->isPipelineExecutableSpecification
        );

        $this->isPipelineExecutableSpecification
            ->expects($this->never())
            ->method('isSatisfiedBy');

        $this->pipelineRunner
            ->expects($this->never())
            ->method('run');

        // PHP's type system will throw a TypeError when an invalid type is returned
        $this->expectException(\TypeError::class);

        $handler->__invoke($command);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itCallsPipelineRunnerWithCorrectPipeline(): void
    {
        $pipelineId = 'specific-pipeline-789';
        $command = new ExecuteWorkflowCommand($pipelineId);
        $pipeline = $this->createMock(Pipeline::class);
        $context = new Context([
            'processed' => true,
        ]);

        $this->pipelineProvider
            ->method('findPipelineByIdentifier')
            ->willReturn($pipeline);

        $this->isPipelineExecutableSpecification
            ->method('isSatisfiedBy')
            ->willReturn(true);

        $this->pipelineRunner
            ->expects($this->once())
            ->method('run')
            ->with($this->identicalTo($pipeline))
            ->willReturn($context);

        $result = $this->handler->__invoke($command);

        $this->assertSame($context, $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itPassesPipelineToSpecificationCheck(): void
    {
        $pipelineId = 'test-pipeline';
        $command = new ExecuteWorkflowCommand($pipelineId);
        $pipeline = $this->createMock(Pipeline::class);

        $this->pipelineProvider
            ->method('findPipelineByIdentifier')
            ->willReturn($pipeline);

        $this->isPipelineExecutableSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($this->identicalTo($pipeline))
            ->willReturn(false);

        $this->pipelineRunner
            ->expects($this->never())
            ->method('run');

        $this->handler->__invoke($command);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itImplementsCommandHandlerInterface(): void
    {
        $this->assertInstanceOf(\BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandHandler::class, $this->handler);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itHandlesComplexWorkflowExecutionScenario(): void
    {
        $pipelineId = 'complex-etl-pipeline';
        $command = new ExecuteWorkflowCommand($pipelineId);
        $pipeline = $this->createMock(Pipeline::class);

        // Simulate complex result data
        $complexResult = new Context([
            'extracted_records' => 1500,
            'transformed_records' => 1450,
            'loaded_records' => 1450,
            'errors' => [],
            'execution_time' => '2.5s',
        ]);

        $this->pipelineProvider
            ->method('findPipelineByIdentifier')
            ->with($pipelineId)
            ->willReturn($pipeline);

        $this->isPipelineExecutableSpecification
            ->method('isSatisfiedBy')
            ->with($pipeline)
            ->willReturn(true);

        $this->pipelineRunner
            ->method('run')
            ->with($pipeline)
            ->willReturn($complexResult);

        $result = $this->handler->__invoke($command);

        $this->assertSame($complexResult, $result);
        $this->assertSame([
            'extracted_records' => 1500,
            'transformed_records' => 1450,
            'loaded_records' => 1450,
            'errors' => [],
            'execution_time' => '2.5s',
        ], $result->getInput());
    }
}
