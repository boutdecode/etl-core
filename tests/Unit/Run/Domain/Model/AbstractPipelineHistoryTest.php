<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\AbstractPipelineHistory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\PipelineHistory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractPipelineHistoryTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getShouldReturnPipeline(): void
    {
        $pipeline = $this->createMock(Pipeline::class);
        $history = $this->createConcreteHistory(
            pipeline: $pipeline,
            status: PipelineHistoryStatusEnum::COMPLETED
        );

        $this->assertSame($pipeline, $history->getPipeline());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getShouldReturnStatus(): void
    {
        $history = $this->createConcreteHistory(
            pipeline: $this->createMock(Pipeline::class),
            status: PipelineHistoryStatusEnum::FAILED
        );

        $this->assertSame(PipelineHistoryStatusEnum::FAILED, $history->getStatus());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getShouldReturnCreatedAt(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 10:30:00');
        $history = $this->createConcreteHistory(
            pipeline: $this->createMock(Pipeline::class),
            status: PipelineHistoryStatusEnum::COMPLETED,
            createdAt: $createdAt
        );

        $this->assertSame($createdAt, $history->getCreatedAt());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getShouldReturnInput(): void
    {
        $input = [
            'key' => 'value',
        ];
        $history = $this->createConcreteHistory(
            pipeline: $this->createMock(Pipeline::class),
            status: PipelineHistoryStatusEnum::COMPLETED,
            input: $input
        );

        $this->assertSame($input, $history->getInput());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getShouldReturnNullInput(): void
    {
        $history = $this->createConcreteHistory(
            pipeline: $this->createMock(Pipeline::class),
            status: PipelineHistoryStatusEnum::COMPLETED,
            input: null
        );

        $this->assertNull($history->getInput());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getShouldReturnResult(): void
    {
        $result = [
            'output' => 'data',
        ];
        $history = $this->createConcreteHistory(
            pipeline: $this->createMock(Pipeline::class),
            status: PipelineHistoryStatusEnum::COMPLETED,
            result: $result
        );

        $this->assertSame($result, $history->getResult());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getShouldReturnNullResult(): void
    {
        $history = $this->createConcreteHistory(
            pipeline: $this->createMock(Pipeline::class),
            status: PipelineHistoryStatusEnum::COMPLETED,
            result: null
        );

        $this->assertNull($history->getResult());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getShouldReturnStepHistories(): void
    {
        $stepHistory1 = $this->createMock(StepHistory::class);
        $stepHistory2 = $this->createMock(StepHistory::class);
        $stepHistories = [$stepHistory1, $stepHistory2];

        $history = $this->createConcreteHistory(
            pipeline: $this->createMock(Pipeline::class),
            status: PipelineHistoryStatusEnum::COMPLETED,
            stepHistories: $stepHistories
        );

        $this->assertSame($stepHistories, $history->getStepHistories());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getShouldReturnEmptyStepHistories(): void
    {
        $history = $this->createConcreteHistory(
            pipeline: $this->createMock(Pipeline::class),
            status: PipelineHistoryStatusEnum::COMPLETED,
            stepHistories: []
        );

        $this->assertSame([], $history->getStepHistories());
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function implementsPipelineHistoryInterface(): void
    {
        $history = $this->createConcreteHistory(
            pipeline: $this->createMock(Pipeline::class),
            status: PipelineHistoryStatusEnum::COMPLETED
        );

        $this->assertInstanceOf(PipelineHistory::class, $history);
    }

    /**
     * @param StepHistory[] $stepHistories
     */
    private function createConcreteHistory(
        Pipeline $pipeline,
        PipelineHistoryStatusEnum $status,
        ?\DateTimeImmutable $createdAt = null,
        mixed $input = null,
        mixed $result = null,
        array $stepHistories = []
    ): AbstractPipelineHistory {
        return new class($pipeline, $status, $createdAt ?? new \DateTimeImmutable(), $input, $result, $stepHistories) extends AbstractPipelineHistory {
            /**
             * @param StepHistory[] $stepHistories
             */
            public function __construct(
                Pipeline $pipeline,
                PipelineHistoryStatusEnum $status,
                \DateTimeImmutable $createdAt,
                mixed $input,
                mixed $result,
                array $stepHistories
            ) {
                $this->pipeline = $pipeline;
                $this->status = $status;
                $this->createdAt = $createdAt;
                $this->input = $input;
                $this->result = $result;
                $this->stepHistories = $stepHistories;
            }
        };
    }
}
