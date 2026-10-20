<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Infrastructure\Console;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\WorkflowProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\PipelineFactory;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\InMemoryWorkflow;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use BoutDeCode\ETLCoreBundle\Run\Domain\Runner\PipelineRunner;
use BoutDeCode\ETLCoreBundle\Run\Domain\Specification\IsPipelineExecutableSpecification;
use BoutDeCode\ETLCoreBundle\Run\Domain\Workflow\PipelineWorkflow;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Console\ExecuteWorkflowConsoleCommand;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Console\WorkflowFileLoader;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[AllowMockObjectsWithoutExpectations]
class ExecuteWorkflowConsoleCommandTest extends TestCase
{
    private WorkflowProvider&MockObject $workflowProvider;

    private PipelineFactory&MockObject $pipelineFactory;

    private PipelinePersister&MockObject $pipelinePersister;

    private PipelineWorkflow&MockObject $pipelineWorkflow;

    private PipelineRunner&MockObject $pipelineRunner;

    private IsPipelineExecutableSpecification&MockObject $specification;

    private WorkflowFileLoader&MockObject $fileLoader;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->workflowProvider = $this->createMock(WorkflowProvider::class);
        $this->pipelineFactory = $this->createMock(PipelineFactory::class);
        $this->pipelinePersister = $this->createMock(PipelinePersister::class);
        $this->pipelineWorkflow = $this->createMock(PipelineWorkflow::class);
        $this->pipelineRunner = $this->createMock(PipelineRunner::class);
        $this->specification = $this->createMock(IsPipelineExecutableSpecification::class);
        $this->fileLoader = $this->createMock(WorkflowFileLoader::class);

        $this->tester = new CommandTester(new ExecuteWorkflowConsoleCommand(
            $this->workflowProvider,
            $this->pipelineFactory,
            $this->pipelinePersister,
            $this->pipelineWorkflow,
            $this->pipelineRunner,
            $this->specification,
            $this->fileLoader,
        ));
    }

    #[Test]
    public function executeShouldBeInvalidWithoutCodeNorFile(): void
    {
        $this->pipelineRunner->expects($this->never())->method('run');

        $this->assertSame(Command::INVALID, $this->tester->execute([]));
    }

    #[Test]
    public function executeShouldBeInvalidWithCodeAndFile(): void
    {
        $this->pipelineRunner->expects($this->never())->method('run');

        $this->assertSame(Command::INVALID, $this->tester->execute([
            'workflow' => 'code',
            '--file' => 'wf.yaml',
        ]));
    }

    #[Test]
    public function executeShouldFailWhenWorkflowIsUnknown(): void
    {
        $this->workflowProvider->method('findWorkflowByIdentifier')->with('unknown')->willReturn(null);
        $this->pipelineRunner->expects($this->never())->method('run');

        $this->assertSame(Command::FAILURE, $this->tester->execute([
            'workflow' => 'unknown',
        ]));
        $this->assertStringContainsString('not found', $this->tester->getDisplay());
    }

    #[Test]
    public function executeShouldRunPipelineFromWorkflowCode(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getName')->willReturn('wf');
        $pipeline = $this->arrangePipeline();

        $this->workflowProvider->method('findWorkflowByIdentifier')->with('wf-code')->willReturn($workflow);
        $this->pipelineFactory->expects($this->once())
            ->method('createFromWorkflow')
            ->with($workflow, 'wf pipeline', [], [
                'foo' => 'bar',
            ])
            ->willReturn($pipeline);
        $this->pipelinePersister->expects($this->once())->method('create')->with($pipeline)->willReturnArgument(0);
        $this->pipelineWorkflow->expects($this->once())->method('schedule')->with($pipeline);
        $this->pipelineRunner->expects($this->once())->method('run')->with($pipeline)->willReturn(new Context(''));

        $this->assertSame(
            Command::SUCCESS,
            $this->tester->execute([
                'workflow' => 'wf-code',
                '--input' => '{"foo":"bar"}',
            ]),
        );
    }

    #[Test]
    public function executeShouldRunPipelineFromFileWithInMemoryWorkflow(): void
    {
        $pipeline = $this->arrangePipeline();

        $this->fileLoader->method('load')->with('wf.yaml')->willReturn([
            'name' => 'from file',
        ]);
        $this->workflowProvider->expects($this->never())->method('findWorkflowByIdentifier');
        $this->pipelineFactory->expects($this->once())
            ->method('createFromWorkflow')
            ->with($this->isInstanceOf(InMemoryWorkflow::class), 'from file pipeline', [], [])
            ->willReturn($pipeline);
        $this->pipelinePersister->method('create')->willReturnArgument(0);
        $this->pipelineRunner->expects($this->once())->method('run')->willReturn(new Context(''));

        $this->assertSame(Command::SUCCESS, $this->tester->execute([
            '--file' => 'wf.yaml',
        ]));
    }

    #[Test]
    public function executeShouldFailWhenFileCannotBeLoaded(): void
    {
        $this->fileLoader->method('load')->willThrowException(new \InvalidArgumentException('boom'));
        $this->pipelineRunner->expects($this->never())->method('run');

        $this->assertSame(Command::FAILURE, $this->tester->execute([
            '--file' => 'wf.yaml',
        ]));
        $this->assertStringContainsString('boom', $this->tester->getDisplay());
    }

    #[Test]
    public function executeShouldFailWhenFileDescribesInvalidWorkflow(): void
    {
        $this->fileLoader->method('load')->willReturn([]);
        $this->pipelineRunner->expects($this->never())->method('run');

        $this->assertSame(Command::FAILURE, $this->tester->execute([
            '--file' => 'wf.yaml',
        ]));
    }

    #[Test]
    public function executeShouldFailWhenInputIsNotValidJson(): void
    {
        $this->pipelineRunner->expects($this->never())->method('run');

        $this->assertSame(Command::FAILURE, $this->tester->execute([
            'workflow' => 'code',
            '--input' => '{nope',
        ]));
    }

    #[Test]
    public function executeShouldFailWhenPipelineIsNotExecutable(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $pipeline = $this->arrangePipeline(false);

        $this->workflowProvider->method('findWorkflowByIdentifier')->willReturn($workflow);
        $this->pipelineFactory->method('createFromWorkflow')->willReturn($pipeline);
        $this->pipelinePersister->method('create')->willReturnArgument(0);
        $this->pipelineRunner->expects($this->never())->method('run');

        $this->assertSame(Command::FAILURE, $this->tester->execute([
            'workflow' => 'code',
        ]));
    }

    #[Test]
    public function executeShouldFailWhenContextHasErrors(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $pipeline = $this->arrangePipeline();
        $context = new Context('');
        $context->setResult('extract', [
            'error' => 'step exploded',
        ]);

        $this->workflowProvider->method('findWorkflowByIdentifier')->willReturn($workflow);
        $this->pipelineFactory->method('createFromWorkflow')->willReturn($pipeline);
        $this->pipelinePersister->method('create')->willReturnArgument(0);
        $this->pipelineRunner->method('run')->willReturn($context);

        $this->assertSame(Command::FAILURE, $this->tester->execute([
            'workflow' => 'code',
        ]));
        $this->assertStringContainsString('step exploded', $this->tester->getDisplay());
    }

    private function arrangePipeline(bool $executable = true): Pipeline&MockObject
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getId')->willReturn('pipeline-id');
        $this->specification->method('isSatisfiedBy')->willReturn($executable);

        return $pipeline;
    }
}
