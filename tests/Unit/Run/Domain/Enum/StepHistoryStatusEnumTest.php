<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Enum;

use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\StepHistoryStatusEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StepHistoryStatusEnumTest extends TestCase
{
    #[Test]
    public function shouldHaveCompletedStatus(): void
    {
        $status = StepHistoryStatusEnum::COMPLETED;

        $this->assertSame('completed', $status->value);
        $this->assertInstanceOf(StepHistoryStatusEnum::class, $status);
    }

    #[Test]
    public function shouldHaveFailedStatus(): void
    {
        $status = StepHistoryStatusEnum::FAILED;

        $this->assertSame('failed', $status->value);
        $this->assertInstanceOf(StepHistoryStatusEnum::class, $status);
    }

    #[Test]
    public function shouldBeAbleToCreateFromValue(): void
    {
        $completedFromValue = StepHistoryStatusEnum::from('completed');
        $failedFromValue = StepHistoryStatusEnum::from('failed');

        $this->assertSame(StepHistoryStatusEnum::COMPLETED, $completedFromValue);
        $this->assertSame(StepHistoryStatusEnum::FAILED, $failedFromValue);
    }

    #[Test]
    public function shouldBeAbleToTryCreateFromValue(): void
    {
        $completed = StepHistoryStatusEnum::tryFrom('completed');
        $failed = StepHistoryStatusEnum::tryFrom('failed');
        $invalid = StepHistoryStatusEnum::tryFrom('invalid');

        $this->assertSame(StepHistoryStatusEnum::COMPLETED, $completed);
        $this->assertSame(StepHistoryStatusEnum::FAILED, $failed);
        $this->assertNull($invalid);
    }

    #[Test]
    public function shouldGetAllCases(): void
    {
        $cases = StepHistoryStatusEnum::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(StepHistoryStatusEnum::COMPLETED, $cases);
        $this->assertContains(StepHistoryStatusEnum::FAILED, $cases);
    }

    #[Test]
    public function shouldBeComparable(): void
    {
        $completed1 = StepHistoryStatusEnum::COMPLETED;
        $completed2 = StepHistoryStatusEnum::COMPLETED;
        $failed = StepHistoryStatusEnum::FAILED;

        $this->assertTrue($completed1 === $completed2);
        $this->assertFalse($completed1 === $failed);
    }

    #[Test]
    public function shouldBeUsableInSwitchStatements(): void
    {
        $status = StepHistoryStatusEnum::COMPLETED;

        $result = match ($status) {
            StepHistoryStatusEnum::COMPLETED => 'step_success',
            StepHistoryStatusEnum::FAILED => 'step_error',
        };

        $this->assertSame('step_success', $result);

        $status = StepHistoryStatusEnum::FAILED;

        $result = match ($status) {
            StepHistoryStatusEnum::COMPLETED => 'step_success',
            StepHistoryStatusEnum::FAILED => 'step_error',
        };

        $this->assertSame('step_error', $result);
    }

    #[Test]
    public function shouldHandleSerializationCorrectly(): void
    {
        $status = StepHistoryStatusEnum::COMPLETED;
        $serialized = serialize($status);
        $unserialized = unserialize($serialized);

        $this->assertSame($status, $unserialized);
        $this->assertSame('completed', $unserialized->value);
    }

    #[Test]
    public function shouldWorkWithArrays(): void
    {
        $statuses = [
            StepHistoryStatusEnum::COMPLETED,
            StepHistoryStatusEnum::FAILED,
        ];

        $this->assertCount(2, $statuses);
        $this->assertTrue(in_array(StepHistoryStatusEnum::COMPLETED, $statuses, true));
        $this->assertTrue(in_array(StepHistoryStatusEnum::FAILED, $statuses, true));
    }

    #[Test]
    public function shouldAllowStatusChecking(): void
    {
        $completedStatus = StepHistoryStatusEnum::COMPLETED;
        $failedStatus = StepHistoryStatusEnum::FAILED;

        // Simulate status checking logic
        $isCompleted = $completedStatus === StepHistoryStatusEnum::COMPLETED;
        $isFailed = $failedStatus === StepHistoryStatusEnum::FAILED;

        $this->assertTrue($isCompleted);
        $this->assertTrue($isFailed);
        $this->assertFalse($completedStatus === StepHistoryStatusEnum::FAILED);
        $this->assertFalse($failedStatus === StepHistoryStatusEnum::COMPLETED);
    }
}
