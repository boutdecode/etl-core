<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;

interface MetricsCollector
{
    public function recordPipelineStarted(Pipeline $pipeline, Context $context): void;

    public function recordPipelineCompleted(Pipeline $pipeline, Context $context, float $duration): void;

    public function recordPipelineFailed(Pipeline $pipeline, Context $context, \Throwable $exception, float $duration): void;

    public function recordStepStarted(string $stepCode, string $stepName, Context $context): void;

    public function recordStepCompleted(string $stepCode, string $stepName, Context $context, float $duration, int $recordsProcessed = 0): void;

    public function recordStepFailed(string $stepCode, string $stepName, Context $context, \Throwable $exception, float $duration): void;

    /**
     * @param array<string, string> $tags
     */
    public function incrementCounter(string $metricName, array $tags = []): void;

    /**
     * @param array<string, string> $tags
     */
    public function recordTiming(string $metricName, float $duration, array $tags = []): void;

    /**
     * @param array<string, string> $tags
     */
    public function recordGauge(string $metricName, float $value, array $tags = []): void;

    /**
     * Get aggregated metrics for reporting
     * @return array<string, mixed>
     */
    public function getMetrics(): array;

    /**
     * Reset all collected metrics
     */
    public function reset(): void;
}
