<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;

abstract class AbstractPipeline implements Pipeline
{
    protected \DateTimeImmutable $createdAt;

    protected ?\DateTimeImmutable $scheduledAt;

    protected ?\DateTimeImmutable $startedAt;

    protected ?\DateTimeImmutable $finishedAt;

    protected PipelineStatus $status;

    protected Workflow $workflow;

    /**
     * @var Step[]
     */
    protected iterable $steps = [];

    /**
     * @var Step[]
     */
    protected iterable $runnableSteps = [];

    /**
     * @var array<string, mixed>
     */
    protected array $configuration = [];

    /**
     * @var array<string, mixed>
     */
    protected array $input = [];

    abstract public function getId(): string;

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getStatus(): PipelineStatus
    {
        return $this->status;
    }

    public function getSteps(): iterable
    {
        return $this->steps;
    }

    /**
     * @return iterable<Step>
     */
    public function getRunnableSteps(): iterable
    {
        return $this->runnableSteps;
    }

    /**
     * @param iterable<Step> $runnableSteps
     */
    public function setRunnableSteps(iterable $runnableSteps): void
    {
        $this->runnableSteps = $runnableSteps;
    }

    public function getStepFromRunnableStep(Step $runnableStep): ?Step
    {
        foreach ($this->steps as $step) {
            if ($step->getName() === $runnableStep->getName()) {
                return $step;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @return array<string, mixed>
     */
    public function getInput(): array
    {
        return $this->input;
    }

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }

    public function start(): void
    {
        $this->startedAt = new \DateTimeImmutable();
    }

    public function finish(): void
    {
        $this->finishedAt = new \DateTimeImmutable();
    }

    public function reset(): void
    {
        $this->scheduledAt = new \DateTimeImmutable();
        $this->startedAt = null;
        $this->finishedAt = null;
    }
}
