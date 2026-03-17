<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Model;

use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\AbstractStepHistory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Model\StepHistory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractStepHistoryTest extends TestCase
{
    #[Test]
    public function getShouldReturnStatus(): void
    {
        $history = $this->createConcreteHistory(status: StepHistoryStatusEnum::COMPLETED);

        $this->assertSame(StepHistoryStatusEnum::COMPLETED, $history->getStatus());
    }

    #[Test]
    public function getShouldReturnFailedStatus(): void
    {
        $history = $this->createConcreteHistory(status: StepHistoryStatusEnum::FAILED);

        $this->assertSame(StepHistoryStatusEnum::FAILED, $history->getStatus());
    }

    #[Test]
    public function getShouldReturnCreatedAt(): void
    {
        $createdAt = new \DateTimeImmutable('2024-06-01 12:00:00');
        $history = $this->createConcreteHistory(
            status: StepHistoryStatusEnum::COMPLETED,
            createdAt: $createdAt
        );

        $this->assertSame($createdAt, $history->getCreatedAt());
    }

    #[Test]
    public function getShouldReturnInput(): void
    {
        $input = [
            'row' => 1,
            'value' => 'test',
        ];
        $history = $this->createConcreteHistory(
            status: StepHistoryStatusEnum::COMPLETED,
            input: $input
        );

        $this->assertSame($input, $history->getInput());
    }

    #[Test]
    public function getShouldReturnNullInput(): void
    {
        $history = $this->createConcreteHistory(
            status: StepHistoryStatusEnum::COMPLETED,
            input: null
        );

        $this->assertNull($history->getInput());
    }

    #[Test]
    public function getShouldReturnResult(): void
    {
        $result = [
            'transformed' => 'data',
        ];
        $history = $this->createConcreteHistory(
            status: StepHistoryStatusEnum::COMPLETED,
            result: $result
        );

        $this->assertSame($result, $history->getResult());
    }

    #[Test]
    public function getShouldReturnNullResult(): void
    {
        $history = $this->createConcreteHistory(
            status: StepHistoryStatusEnum::COMPLETED,
            result: null
        );

        $this->assertNull($history->getResult());
    }

    #[Test]
    public function implementsStepHistoryInterface(): void
    {
        $history = $this->createConcreteHistory(status: StepHistoryStatusEnum::COMPLETED);

        $this->assertInstanceOf(StepHistory::class, $history);
    }

    private function createConcreteHistory(
        StepHistoryStatusEnum $status,
        ?\DateTimeImmutable $createdAt = null,
        mixed $input = null,
        mixed $result = null
    ): AbstractStepHistory {
        return new class($status, $createdAt ?? new \DateTimeImmutable(), $input, $result) extends AbstractStepHistory {
            public function __construct(
                StepHistoryStatusEnum $status,
                \DateTimeImmutable $createdAt,
                mixed $input,
                mixed $result
            ) {
                $this->status = $status;
                $this->createdAt = $createdAt;
                $this->input = $input;
                $this->result = $result;
            }
        };
    }
}
