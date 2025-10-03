<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Core\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractPipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Step;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractPipelineTest extends TestCase
{
    private TestPipeline $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = new TestPipeline();
    }

    #[Test]
    public function getCreatedAtShouldReturnCreatedDate(): void
    {
        $createdAt = $this->pipeline->getCreatedAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $createdAt);
    }

    #[Test]
    public function getScheduledAtShouldReturnNullByDefault(): void
    {
        $this->assertNull($this->pipeline->getScheduledAt());
    }

    #[Test]
    public function getStartedAtShouldReturnNullByDefault(): void
    {
        $this->assertNull($this->pipeline->getStartedAt());
    }

    #[Test]
    public function getFinishedAtShouldReturnNullByDefault(): void
    {
        $this->assertNull($this->pipeline->getFinishedAt());
    }

    #[Test]
    public function getStatusShouldReturnDefaultStatus(): void
    {
        $this->assertSame(PipelineStatus::PENDING, $this->pipeline->getStatus());
    }

    #[Test]
    public function getStepsShouldReturnEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->pipeline->getSteps());
    }

    #[Test]
    public function getRunnableStepsShouldReturnEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->pipeline->getRunnableSteps());
    }

    #[Test]
    public function getConfigurationShouldReturnEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->pipeline->getConfiguration());
    }

    #[Test]
    public function getInputShouldReturnEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->pipeline->getInput());
    }

    #[Test]
    public function startShouldSetStartedAtTime(): void
    {
        $beforeStart = new \DateTimeImmutable();

        $this->pipeline->start();

        $afterStart = new \DateTimeImmutable();
        $startedAt = $this->pipeline->getStartedAt();

        $this->assertNotNull($startedAt);
        $this->assertGreaterThanOrEqual($beforeStart, $startedAt);
        $this->assertLessThanOrEqual($afterStart, $startedAt);
    }

    #[Test]
    public function finishShouldSetFinishedAtTime(): void
    {
        $beforeFinish = new \DateTimeImmutable();

        $this->pipeline->finish();

        $afterFinish = new \DateTimeImmutable();
        $finishedAt = $this->pipeline->getFinishedAt();

        $this->assertNotNull($finishedAt);
        $this->assertGreaterThanOrEqual($beforeFinish, $finishedAt);
        $this->assertLessThanOrEqual($afterFinish, $finishedAt);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getWorkflowShouldReturnSetWorkflow(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $this->pipeline->setWorkflow($workflow);

        $this->assertSame($workflow, $this->pipeline->getWorkflow());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function setStepsShouldUpdateSteps(): void
    {
        $step1 = $this->createMock(Step::class);
        $step2 = $this->createMock(Step::class);
        $steps = [$step1, $step2];

        $this->pipeline->setSteps($steps);

        $this->assertSame($steps, $this->pipeline->getSteps());
    }

    #[Test]
    public function setConfigurationShouldUpdateConfiguration(): void
    {
        $configuration = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->pipeline->setConfiguration($configuration);

        $this->assertSame($configuration, $this->pipeline->getConfiguration());
    }
}

// Test class extending AbstractPipeline for testing purposes
class TestPipeline extends AbstractPipeline
{
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->scheduledAt = null;
        $this->startedAt = null;
        $this->finishedAt = null;
        $this->status = PipelineStatus::PENDING;
        $this->steps = [];
        $this->runnableSteps = [];
        $this->configuration = [];
        $this->input = [];
    }

    public function getId(): string
    {
        return 'test-pipeline-id';
    }

    public function setWorkflow(Workflow $workflow): void
    {
        $this->workflow = $workflow;
    }

    public function setSteps(iterable $steps): void
    {
        $this->steps = $steps;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
