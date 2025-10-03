<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Run\Infrastructure\Instrumentation;

use BoutDeCode\ETLCoreBundle\Core\Domain\DTO\Context;
use BoutDeCode\ETLCoreBundle\Core\Domain\Model\Pipeline;
use BoutDeCode\ETLCoreBundle\Run\Domain\Instrumentation\MetricsCollector as MetricsCollectorInterface;

class MetricsCollector implements MetricsCollectorInterface
{
    /**
     * @var array<string, array{name: string, value: int, tags: array<string, string>}>
     */
    private array $counters = [];

    /**
     * @var array<string, array{name: string, values: list<float>, tags: array<string, string>, count: int, sum: float, min: float|null, max: float|null}>
     */
    private array $timings = [];

    /**
     * @var array<string, array{name: string, value: float, tags: array<string, string>, timestamp: float}>
     */
    private array $gauges = [];

    /**
     * @var array<string, array{status: string, started_at: float, pipeline_status: string, step_count: int, steps: list<mixed>, completed_at?: float, duration?: float, final_status?: string, failed_at?: float, error?: array{type: string, message: string, code: int|string}}>
     */
    private array $pipelines = [];

    /**
     * @var array<string, array{code: string, name: string, status: string, started_at: float, completed_at?: float, duration?: float, records_processed?: int, failed_at?: float, error?: array{type: string, message: string, code: int|string}}>
     */
    private array $steps = [];

    public function recordPipelineStarted(Pipeline $pipeline, Context $context): void
    {
        $pipelineId = spl_object_hash($pipeline);

        $this->pipelines[$pipelineId] = [
            'status' => 'started',
            'started_at' => microtime(true),
            'pipeline_status' => $pipeline->getStatus()->value,
            'step_count' => count(iterator_to_array($pipeline->getSteps())),
            'steps' => [],
        ];

        $this->incrementCounter('pipeline.started', [
            'status' => $pipeline->getStatus()->value,
        ]);
    }

    public function recordPipelineCompleted(Pipeline $pipeline, Context $context, float $duration): void
    {
        $pipelineId = spl_object_hash($pipeline);

        if (isset($this->pipelines[$pipelineId])) {
            $this->pipelines[$pipelineId]['status'] = 'completed';
            $this->pipelines[$pipelineId]['completed_at'] = microtime(true);
            $this->pipelines[$pipelineId]['duration'] = $duration;
            $this->pipelines[$pipelineId]['final_status'] = $pipeline->getStatus()->value;
        }

        $this->incrementCounter('pipeline.completed', [
            'status' => $pipeline->getStatus()->value,
        ]);

        $this->recordTiming('pipeline.duration', $duration, [
            'status' => $pipeline->getStatus()->value,
        ]);
    }

    public function recordPipelineFailed(Pipeline $pipeline, Context $context, \Throwable $exception, float $duration): void
    {
        $pipelineId = spl_object_hash($pipeline);

        if (isset($this->pipelines[$pipelineId])) {
            $this->pipelines[$pipelineId]['status'] = 'failed';
            $this->pipelines[$pipelineId]['failed_at'] = microtime(true);
            $this->pipelines[$pipelineId]['duration'] = $duration;
            $this->pipelines[$pipelineId]['error'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }

        $this->incrementCounter('pipeline.failed', [
            'error_type' => get_class($exception),
        ]);

        $this->recordTiming('pipeline.duration', $duration, [
            'status' => 'failed',
            'error_type' => get_class($exception),
        ]);
    }

    public function recordStepStarted(string $stepCode, string $stepName, Context $context): void
    {
        $stepId = $stepCode . '_' . uniqid();

        $this->steps[$stepId] = [
            'code' => $stepCode,
            'name' => $stepName,
            'status' => 'started',
            'started_at' => microtime(true),
        ];

        $this->incrementCounter('step.started', [
            'step_code' => $stepCode,
            'step_type' => $this->extractStepType($stepCode),
        ]);
    }

    public function recordStepCompleted(string $stepCode, string $stepName, Context $context, float $duration, int $recordsProcessed = 0): void
    {
        $stepId = $this->findStepId($stepCode, 'started');

        if ($stepId !== null && isset($this->steps[$stepId])) {
            $this->steps[$stepId]['status'] = 'completed';
            $this->steps[$stepId]['completed_at'] = microtime(true);
            $this->steps[$stepId]['duration'] = $duration;
            $this->steps[$stepId]['records_processed'] = $recordsProcessed;
        }

        $this->incrementCounter('step.completed', [
            'step_code' => $stepCode,
            'step_type' => $this->extractStepType($stepCode),
        ]);

        $this->recordTiming('step.duration', $duration, [
            'step_code' => $stepCode,
            'step_type' => $this->extractStepType($stepCode),
        ]);

        if ($recordsProcessed > 0) {
            $this->recordGauge('step.records_processed', $recordsProcessed, [
                'step_code' => $stepCode,
                'step_type' => $this->extractStepType($stepCode),
            ]);

            if ($duration > 0) {
                $throughput = $recordsProcessed / $duration;
                $this->recordGauge('step.throughput', $throughput, [
                    'step_code' => $stepCode,
                    'step_type' => $this->extractStepType($stepCode),
                ]);
            }
        }
    }

    public function recordStepFailed(string $stepCode, string $stepName, Context $context, \Throwable $exception, float $duration): void
    {
        $stepId = $this->findStepId($stepCode, 'started');

        if ($stepId !== null && isset($this->steps[$stepId])) {
            $this->steps[$stepId]['status'] = 'failed';
            $this->steps[$stepId]['failed_at'] = microtime(true);
            $this->steps[$stepId]['duration'] = $duration;
            $this->steps[$stepId]['error'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }

        $this->incrementCounter('step.failed', [
            'step_code' => $stepCode,
            'step_type' => $this->extractStepType($stepCode),
            'error_type' => get_class($exception),
        ]);

        $this->recordTiming('step.duration', $duration, [
            'step_code' => $stepCode,
            'step_type' => $this->extractStepType($stepCode),
            'status' => 'failed',
        ]);
    }

    /**
     * @param array<string, string> $tags
     */
    public function incrementCounter(string $metricName, array $tags = []): void
    {
        $key = $this->buildMetricKey($metricName, $tags);

        if (! isset($this->counters[$key])) {
            $this->counters[$key] = [
                'name' => $metricName,
                'value' => 0,
                'tags' => $tags,
            ];
        }

        $this->counters[$key]['value']++;
    }

    /**
     * @param array<string, string> $tags
     */
    public function recordTiming(string $metricName, float $duration, array $tags = []): void
    {
        $key = $this->buildMetricKey($metricName, $tags);

        if (! isset($this->timings[$key])) {
            $this->timings[$key] = [
                'name' => $metricName,
                'values' => [],
                'tags' => $tags,
                'count' => 0,
                'sum' => 0.0,
                'min' => null,
                'max' => null,
            ];
        }

        $metric = &$this->timings[$key];
        $metric['values'][] = $duration;
        $metric['count']++;
        $metric['sum'] += $duration;
        $metric['min'] = $metric['min'] === null ? $duration : min($metric['min'], $duration);
        $metric['max'] = $metric['max'] === null ? $duration : max($metric['max'], $duration);
    }

    /**
     * @param array<string, string> $tags
     */
    public function recordGauge(string $metricName, float $value, array $tags = []): void
    {
        $key = $this->buildMetricKey($metricName, $tags);

        $this->gauges[$key] = [
            'name' => $metricName,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        $processedTimings = [];
        foreach ($this->timings as $key => $timing) {
            $processedTimings[$key] = [
                'name' => $timing['name'],
                'tags' => $timing['tags'],
                'count' => $timing['count'],
                'sum' => $timing['sum'],
                'min' => $timing['min'],
                'max' => $timing['max'],
                'avg' => $timing['count'] > 0 ? $timing['sum'] / $timing['count'] : 0,
            ];
        }

        return [
            'counters' => $this->counters,
            'timings' => $processedTimings,
            'gauges' => $this->gauges,
            'pipelines' => $this->pipelines,
            'steps' => $this->steps,
            'summary' => $this->generateSummary(),
        ];
    }

    public function reset(): void
    {
        $this->counters = [];
        $this->timings = [];
        $this->gauges = [];
        $this->pipelines = [];
        $this->steps = [];
    }

    /**
     * @param array<string, string> $tags
     */
    private function buildMetricKey(string $metricName, array $tags): string
    {
        ksort($tags);
        $tagString = http_build_query($tags);

        return $metricName . '|' . $tagString;
    }

    private function extractStepType(string $stepCode): string
    {
        $parts = explode('.', $stepCode);

        return $parts[1] ?? 'unknown';
    }

    private function findStepId(string $stepCode, string $status): ?string
    {
        $latestStepId = null;
        $latestStartedAt = 0.0;

        foreach ($this->steps as $stepId => $step) {
            if ($step['code'] !== $stepCode || $step['status'] !== $status) {
                continue;
            }
            $startedAt = $step['started_at'];
            if ($startedAt > $latestStartedAt) {
                $latestStartedAt = $startedAt;
                $latestStepId = $stepId;
            }
        }

        return $latestStepId;
    }

    /**
     * @return array<string, mixed>
     */
    private function generateSummary(): array
    {
        $pipelineStats = [
            'total' => count($this->pipelines),
            'started' => 0,
            'completed' => 0,
            'failed' => 0,
        ];

        $stepStats = [
            'total' => count($this->steps),
            'started' => 0,
            'completed' => 0,
            'failed' => 0,
        ];

        foreach ($this->pipelines as $pipeline) {
            $stat = $pipeline['status'];
            if (isset($pipelineStats[$stat])) {
                $pipelineStats[$stat]++;
            }
        }

        foreach ($this->steps as $step) {
            $stat = $step['status'];
            if (isset($stepStats[$stat])) {
                $stepStats[$stat]++;
            }
        }

        return [
            'pipelines' => $pipelineStats,
            'steps' => $stepStats,
            'generated_at' => date('c'),
        ];
    }
}
