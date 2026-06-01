<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Model;

abstract class AbstractPlannedTask implements PlannedTask
{
    protected string $name;

    protected bool $enabled;

    protected Workflow $workflow;

    protected ?Pipeline $pipeline;

    protected string $schedule;

    /**
     * @var array<string, mixed>
     */
    protected array $input = [];

    /**
     * @var array<string, mixed>
     */
    protected array $configuration = [];

    protected \DateTimeImmutable $createdAt;

    protected ?\DateTimeImmutable $updatedAt;

    abstract public function getId(): string;

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }

    public function setPipeline(?Pipeline $pipeline): void
    {
        $this->pipeline = $pipeline;
    }

    public function getSchedule(): string
    {
        return $this->schedule;
    }

    public function getInput(): array
    {
        return $this->input;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
