<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler;

use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger\ExecutePipeline;
use BoutDeCode\ETLCoreBundle\Run\Infrastructure\Scheduler\Messenger\SchedulePlannedTask;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule(name: 'etl')]
final class ETLScheduler implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->with(
                RecurringMessage::cron(
                    '* * * * *',
                    new ExecutePipeline()
                ),
                RecurringMessage::cron(
                    '* * * * *',
                    new SchedulePlannedTask()
                )
            )
        ;
    }
}
