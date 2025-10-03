<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Core\Domain\Enum;

use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PipelineStatusTest extends TestCase
{
    #[Test]
    public function enumShouldHaveCorrectValues(): void
    {
        $this->assertSame('pending', PipelineStatus::PENDING->value);
        $this->assertSame('in_progress', PipelineStatus::IN_PROGRESS->value);
        $this->assertSame('completed', PipelineStatus::COMPLETED->value);
        $this->assertSame('failed', PipelineStatus::FAILED->value);
    }

    #[Test]
    public function fromShouldCreateEnumFromString(): void
    {
        $this->assertSame(PipelineStatus::PENDING, PipelineStatus::from('pending'));
        $this->assertSame(PipelineStatus::IN_PROGRESS, PipelineStatus::from('in_progress'));
        $this->assertSame(PipelineStatus::COMPLETED, PipelineStatus::from('completed'));
        $this->assertSame(PipelineStatus::FAILED, PipelineStatus::from('failed'));
    }

    #[Test]
    public function fromShouldThrowExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        PipelineStatus::from('invalid_status');
    }

    #[Test]
    public function tryFromShouldReturnNullForInvalidValue(): void
    {
        $this->assertNull(PipelineStatus::tryFrom('invalid_status'));
    }

    #[Test]
    public function tryFromShouldReturnEnumForValidValue(): void
    {
        $this->assertSame(PipelineStatus::PENDING, PipelineStatus::tryFrom('pending'));
    }

    #[Test]
    public function casesShouldReturnAllStatuses(): void
    {
        $cases = PipelineStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(PipelineStatus::PENDING, $cases);
        $this->assertContains(PipelineStatus::IN_PROGRESS, $cases);
        $this->assertContains(PipelineStatus::COMPLETED, $cases);
        $this->assertContains(PipelineStatus::FAILED, $cases);
    }
}
