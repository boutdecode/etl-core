<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Model;

abstract class AbstractWorkflow implements Workflow
{
    protected string $name;

    protected ?string $description;

    /**
     * @var array<string, mixed>
     */
    protected array $stepConfiguration;

    /**
     * @var array<string, mixed>
     */
    protected array $configuration;

    protected bool $notifyOnSuccess = false;

    protected bool $notifyOnFailure = false;

    /**
     * @var string[]|null
     */
    protected ?array $notificationProviders = null;

    protected \DateTimeImmutable $createdAt;

    protected ?\DateTimeImmutable $updatedAt = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStepConfiguration(): array
    {
        return $this->stepConfiguration;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function isNotifyOnSuccess(): bool
    {
        return $this->notifyOnSuccess;
    }

    public function isNotifyOnFailure(): bool
    {
        return $this->notifyOnFailure;
    }

    /**
     * @return string[]|null
     */
    public function getNotificationProviders(): ?array
    {
        return $this->notificationProviders;
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
