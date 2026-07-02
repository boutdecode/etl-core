<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Tests\Unit\Run\Infrastructure\Scheduler;

use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\ETLScheduler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ETLSchedulerTest extends TestCase
{
    #[Test]
    public function itUsesTheConfiguredCronExpressionForThePurgeJob(): void
    {
        $scheduler = new ETLScheduler('*/15 * * * *');

        $messages = $scheduler->getSchedule()->getRecurringMessages();

        $purgeMessage = null;

        foreach ($messages as $message) {
            $description = (string) $message->getTrigger();

            if (str_contains($description, '*/15 * * * *')) {
                $purgeMessage = $message;

                break;
            }
        }

        $this->assertNotNull($purgeMessage, 'Expected to find a recurring message using the configured purge cron expression.');
    }

    #[Test]
    public function itIsFinalAndReadonly(): void
    {
        $reflection = new \ReflectionClass(ETLScheduler::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}
