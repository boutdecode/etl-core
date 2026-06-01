<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Core\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractPlannedTask;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Workflow;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractPlannedTaskTest extends TestCase
{
    private TestPlannedTask $task;

    protected function setUp(): void
    {
        $this->task = new TestPlannedTask();
    }

    #[Test]
    public function getNameShouldReturnName(): void
    {
        $this->assertSame('test-task', $this->task->getName());
    }

    #[Test]
    public function isEnabledShouldReturnEnabledState(): void
    {
        $this->assertTrue($this->task->isEnabled());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getWorkflowShouldReturnWorkflow(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $this->task->setWorkflow($workflow);

        $this->assertSame($workflow, $this->task->getWorkflow());
    }

    #[Test]
    public function getPipelineShouldReturnNullByDefault(): void
    {
        $this->assertNull($this->task->getPipeline());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function setPipelineShouldStorePipeline(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $this->task->setPipeline($pipeline);

        $this->assertSame($pipeline, $this->task->getPipeline());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function setPipelineShouldAcceptNull(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $this->task->setPipeline($pipeline);
        $this->task->setPipeline(null);

        $this->assertNull($this->task->getPipeline());
    }

    #[Test]
    public function getScheduleShouldReturnSchedule(): void
    {
        $this->assertSame('* * * * *', $this->task->getSchedule());
    }

    #[Test]
    public function getInputShouldReturnEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->task->getInput());
    }

    #[Test]
    public function getConfigurationShouldReturnEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->task->getConfiguration());
    }

    #[Test]
    public function getCreatedAtShouldReturnCreatedDate(): void
    {
        $createdAt = $this->task->getCreatedAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $createdAt);
    }

    #[Test]
    public function getUpdatedAtShouldReturnNullByDefault(): void
    {
        $this->assertNull($this->task->getUpdatedAt());
    }

    #[Test]
    public function setInputShouldUpdateInput(): void
    {
        $input = [
            'key' => 'value',
            'count' => 42,
        ];
        $this->task->setInput($input);

        $this->assertSame($input, $this->task->getInput());
    }

    #[Test]
    public function setConfigurationShouldUpdateConfiguration(): void
    {
        $configuration = [
            'timeout' => 30,
            'retries' => 3,
        ];
        $this->task->setConfiguration($configuration);

        $this->assertSame($configuration, $this->task->getConfiguration());
    }
}

// Concrete stub extending AbstractPlannedTask for testing purposes
class TestPlannedTask extends AbstractPlannedTask
{
    public function __construct()
    {
        $this->name = 'test-task';
        $this->enabled = true;
        $this->pipeline = null;
        $this->schedule = '* * * * *';
        $this->input = [];
        $this->configuration = [];
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    public function getId(): string
    {
        return 'test-planned-task-id';
    }

    public function setWorkflow(Workflow $workflow): void
    {
        $this->workflow = $workflow;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function setInput(array $input): void
    {
        $this->input = $input;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
