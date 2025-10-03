<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Specification;

use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Specification\IsPipelineExecutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IsPipelineExecutableTest extends TestCase
{
    private IsPipelineExecutable $specification;

    protected function setUp(): void
    {
        $this->specification = new IsPipelineExecutable();
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function isSatisfiedByShouldReturnTrueForPendingPipeline(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStatus')->willReturn(PipelineStatus::PENDING);

        $result = $this->specification->isSatisfiedBy($pipeline);

        $this->assertTrue($result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function isSatisfiedByShouldReturnFalseForInProgressPipeline(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStatus')->willReturn(PipelineStatus::IN_PROGRESS);

        $result = $this->specification->isSatisfiedBy($pipeline);

        $this->assertFalse($result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function isSatisfiedByShouldReturnFalseForCompletedPipeline(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStatus')->willReturn(PipelineStatus::COMPLETED);

        $result = $this->specification->isSatisfiedBy($pipeline);

        $this->assertFalse($result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function isSatisfiedByShouldReturnFalseForFailedPipeline(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $pipeline->method('getStatus')->willReturn(PipelineStatus::FAILED);

        $result = $this->specification->isSatisfiedBy($pipeline);

        $this->assertFalse($result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function specificationShouldImplementInterface(): void
    {
        $this->assertInstanceOf(
            \BoutDeCode\ETLCoreBundle\Run\Domain\Specification\IsPipelineExecutableSpecification::class,
            $this->specification
        );
    }
}
