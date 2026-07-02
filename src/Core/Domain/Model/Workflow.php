<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Model;

interface Workflow
{
    public function getId(): string;

    public function getName(): string;

    public function getDescription(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function getStepConfiguration(): array;

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;

    public function isNotifyOnSuccess(): bool;

    public function isNotifyOnFailure(): bool;

    /**
     * @return string[]|null
     */
    public function getNotificationProviders(): ?array;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): ?\DateTimeImmutable;
}
