<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Domain\Enum;

use BoutDeCode\ETLCoreBundle\Run\Domain\Enum\PipelineHistoryStatusEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineHistoryStatusEnumTest extends TestCase
{
    #[Test]
    public function shouldHaveCompletedStatus(): void
    {
        $status = PipelineHistoryStatusEnum::COMPLETED;

        $this->assertSame('completed', $status->value);
        $this->assertInstanceOf(PipelineHistoryStatusEnum::class, $status);
    }

    #[Test]
    public function shouldHaveFailedStatus(): void
    {
        $status = PipelineHistoryStatusEnum::FAILED;

        $this->assertSame('failed', $status->value);
        $this->assertInstanceOf(PipelineHistoryStatusEnum::class, $status);
    }

    #[Test]
    public function shouldBeAbleToCreateFromValue(): void
    {
        $completedFromValue = PipelineHistoryStatusEnum::from('completed');
        $failedFromValue = PipelineHistoryStatusEnum::from('failed');

        $this->assertSame(PipelineHistoryStatusEnum::COMPLETED, $completedFromValue);
        $this->assertSame(PipelineHistoryStatusEnum::FAILED, $failedFromValue);
    }

    #[Test]
    public function shouldBeAbleToTryCreateFromValue(): void
    {
        $completed = PipelineHistoryStatusEnum::tryFrom('completed');
        $failed = PipelineHistoryStatusEnum::tryFrom('failed');
        $invalid = PipelineHistoryStatusEnum::tryFrom('invalid');

        $this->assertSame(PipelineHistoryStatusEnum::COMPLETED, $completed);
        $this->assertSame(PipelineHistoryStatusEnum::FAILED, $failed);
        $this->assertNull($invalid);
    }

    #[Test]
    public function shouldGetAllCases(): void
    {
        $cases = PipelineHistoryStatusEnum::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(PipelineHistoryStatusEnum::COMPLETED, $cases);
        $this->assertContains(PipelineHistoryStatusEnum::FAILED, $cases);
    }

    #[Test]
    public function shouldBeComparable(): void
    {
        $completed1 = PipelineHistoryStatusEnum::COMPLETED;
        $completed2 = PipelineHistoryStatusEnum::COMPLETED;
        $failed = PipelineHistoryStatusEnum::FAILED;

        $this->assertTrue($completed1 === $completed2);
        $this->assertFalse($completed1 === $failed);
    }

    #[Test]
    public function shouldBeUsableInSwitchStatements(): void
    {
        $status = PipelineHistoryStatusEnum::COMPLETED;

        $result = match ($status) {
            PipelineHistoryStatusEnum::COMPLETED => 'success',
            PipelineHistoryStatusEnum::FAILED => 'error',
        };

        $this->assertSame('success', $result);

        $status = PipelineHistoryStatusEnum::FAILED;

        $result = match ($status) {
            PipelineHistoryStatusEnum::COMPLETED => 'success',
            PipelineHistoryStatusEnum::FAILED => 'error',
        };

        $this->assertSame('error', $result);
    }

    #[Test]
    public function shouldHandleSerializationCorrectly(): void
    {
        $status = PipelineHistoryStatusEnum::COMPLETED;
        $serialized = serialize($status);
        $unserialized = unserialize($serialized);

        $this->assertSame($status, $unserialized);
        $this->assertSame('completed', $unserialized->value);
    }
}
