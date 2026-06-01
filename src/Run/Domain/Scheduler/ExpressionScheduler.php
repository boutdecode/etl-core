<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Scheduler;

interface ExpressionScheduler
{
    public function getNextScheduleFromExpression(string $expression): \DateTimeImmutable;
}
