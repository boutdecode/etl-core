<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Model;

use BoutDeCode\ETLCoreBundle\Core\Domain\Enum\PipelineStatus;

interface Pipeline
{
    public function getId(): string;

    public function getName(): ?string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getScheduledAt(): ?\DateTimeImmutable;

    public function getStartedAt(): ?\DateTimeImmutable;

    public function getFinishedAt(): ?\DateTimeImmutable;

    public function getStatus(): PipelineStatus;

    /**
     * @return Step[]
     */
    public function getSteps(): iterable;

    /**
     * @return iterable<Step>
     */
    public function getRunnableSteps(): iterable;

    /**
     * @param iterable<Step> $runnableSteps
     */
    public function setRunnableSteps(iterable $runnableSteps): void;

    public function getStepFromRunnableStep(Step $runnableStep): ?Step;

    public function getWorkflow(): Workflow;

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;

    /**
     * @return array<string, mixed>
     */
    public function getInput(): array;

    public function schedule(): void;

    public function start(): void;

    public function finish(): void;

    public function reset(): void;

    public function plan(\DateTimeImmutable $scheduledAt): void;
}
