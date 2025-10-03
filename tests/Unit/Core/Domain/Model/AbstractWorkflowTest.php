<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Core\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\AbstractWorkflow;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractWorkflowTest extends TestCase
{
    private TestWorkflow $workflow;

    protected function setUp(): void
    {
        $this->workflow = new TestWorkflow();
    }

    #[Test]
    public function getNameShouldReturnInitializedName(): void
    {
        $this->assertSame('Test Workflow', $this->workflow->getName());
    }

    #[Test]
    public function getDescriptionShouldReturnInitializedDescription(): void
    {
        $this->assertSame('Test Description', $this->workflow->getDescription());
    }

    #[Test]
    public function getStepConfigurationShouldReturnInitializedConfiguration(): void
    {
        $expected = [
            'step1' => 'config1',
            'step2' => 'config2',
        ];
        $this->assertSame($expected, $this->workflow->getStepConfiguration());
    }

    #[Test]
    public function getConfigurationShouldReturnInitializedConfiguration(): void
    {
        $expected = [
            'key1' => 'value1',
        ];
        $this->assertSame($expected, $this->workflow->getConfiguration());
    }

    #[Test]
    public function getCreatedAtShouldReturnCreatedDate(): void
    {
        $createdAt = $this->workflow->getCreatedAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $createdAt);
    }

    #[Test]
    public function getUpdatedAtShouldReturnNullByDefault(): void
    {
        $this->assertNull($this->workflow->getUpdatedAt());
    }

    #[Test]
    public function setNameShouldUpdateName(): void
    {
        $newName = 'Updated Workflow Name';
        $this->workflow->setName($newName);

        $this->assertSame($newName, $this->workflow->getName());
    }

    #[Test]
    public function setDescriptionShouldUpdateDescription(): void
    {
        $newDescription = 'Updated Description';
        $this->workflow->setDescription($newDescription);

        $this->assertSame($newDescription, $this->workflow->getDescription());
    }

    #[Test]
    public function setDescriptionWithNullShouldSetToNull(): void
    {
        $this->workflow->setDescription(null);

        $this->assertNull($this->workflow->getDescription());
    }

    #[Test]
    public function setStepConfigurationShouldUpdateStepConfiguration(): void
    {
        $newStepConfig = [
            'newStep1' => 'newConfig1',
            'newStep2' => 'newConfig2',
        ];
        $this->workflow->setStepConfiguration($newStepConfig);

        $this->assertSame($newStepConfig, $this->workflow->getStepConfiguration());
    }

    #[Test]
    public function setConfigurationShouldUpdateConfiguration(): void
    {
        $newConfig = [
            'newKey1' => 'newValue1',
            'newKey2' => 'newValue2',
        ];
        $this->workflow->setConfiguration($newConfig);

        $this->assertSame($newConfig, $this->workflow->getConfiguration());
    }
}

// Test class extending AbstractWorkflow for testing purposes
class TestWorkflow extends AbstractWorkflow
{
    public function __construct()
    {
        $this->name = 'Test Workflow';
        $this->description = 'Test Description';
        $this->stepConfiguration = [
            'step1' => 'config1',
            'step2' => 'config2',
        ];
        $this->configuration = [
            'key1' => 'value1',
        ];
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setStepConfiguration(array $stepConfiguration): void
    {
        $this->stepConfiguration = $stepConfiguration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
