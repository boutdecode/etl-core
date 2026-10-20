<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Core\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Model\InMemoryWorkflow;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InMemoryWorkflowTest extends TestCase
{
    #[Test]
    public function fromArrayShouldApplyDefaults(): void
    {
        $workflow = InMemoryWorkflow::fromArray([
            'name' => 'wf',
        ]);

        $this->assertSame('wf', $workflow->getName());
        $this->assertNull($workflow->getDescription());
        $this->assertSame([], $workflow->getStepConfiguration());
        $this->assertSame([], $workflow->getConfiguration());
        $this->assertFalse($workflow->isNotifyOnSuccess());
        $this->assertFalse($workflow->isNotifyOnFailure());
        $this->assertNull($workflow->getNotificationProviders());
        $this->assertNull($workflow->getUpdatedAt());
        $this->assertNotSame('', $workflow->getId());
    }

    #[Test]
    public function fromArrayShouldMapAllKeys(): void
    {
        $steps = [[
            'code' => 'a',
            'name' => 'step',
            'order' => 1,
            'configuration' => [],
        ]];

        $workflow = InMemoryWorkflow::fromArray([
            'id' => 'custom-id',
            'name' => 'wf',
            'description' => 'desc',
            'step_configuration' => $steps,
            'configuration' => [
                'timeout' => 10,
            ],
            'notify_on_success' => true,
            'notify_on_failure' => true,
            'notification_providers' => ['email'],
        ]);

        $this->assertSame('custom-id', $workflow->getId());
        $this->assertSame('desc', $workflow->getDescription());
        $this->assertSame($steps, $workflow->getStepConfiguration());
        $this->assertSame([
            'timeout' => 10,
        ], $workflow->getConfiguration());
        $this->assertTrue($workflow->isNotifyOnSuccess());
        $this->assertTrue($workflow->isNotifyOnFailure());
        $this->assertSame(['email'], $workflow->getNotificationProviders());
    }

    #[Test]
    public function fromArrayShouldGenerateDistinctIds(): void
    {
        $this->assertNotSame(
            InMemoryWorkflow::fromArray([
                'name' => 'a',
            ])->getId(),
            InMemoryWorkflow::fromArray([
                'name' => 'a',
            ])->getId(),
        );
    }

    #[Test]
    public function fromArrayShouldRejectMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        InMemoryWorkflow::fromArray([]);
    }

    #[Test]
    public function fromArrayShouldRejectNonStringId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        InMemoryWorkflow::fromArray([
            'name' => 'wf',
            'id' => 12,
        ]);
    }
}
