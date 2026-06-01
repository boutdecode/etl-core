<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Model;

interface PlannedTask
{
    public function getId(): string;

    public function getName(): string;

    public function isEnabled(): bool;

    public function getWorkflow(): Workflow;

    public function getPipeline(): Pipeline|null;

    public function setPipeline(?Pipeline $pipeline): void;

    public function getSchedule(): string;

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;

    /**
     * @return array<string, mixed>
     */
    public function getInput(): array;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): ?\DateTimeImmutable;
}
