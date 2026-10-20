<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Core\Domain\Model;

final class InMemoryWorkflow extends AbstractWorkflow
{
    private function __construct(
        private readonly string $id,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name']) || ! \is_string($data['name']) || $data['name'] === '') {
            throw new \InvalidArgumentException('The "name" key is required and must be a non-empty string.');
        }

        $id = $data['id'] ?? bin2hex(random_bytes(8));
        if (! \is_string($id)) {
            throw new \InvalidArgumentException('The "id" key must be a string.');
        }

        $workflow = new self($id);
        $workflow->name = $data['name'];
        $workflow->description = \is_string($data['description'] ?? null) ? $data['description'] : null;
        $workflow->stepConfiguration = self::toMap($data['step_configuration'] ?? []);
        $workflow->configuration = self::toMap($data['configuration'] ?? []);
        $workflow->notifyOnSuccess = (bool) ($data['notify_on_success'] ?? false);
        $workflow->notifyOnFailure = (bool) ($data['notify_on_failure'] ?? false);
        $workflow->notificationProviders = \is_array($data['notification_providers'] ?? null)
            ? array_values(array_filter($data['notification_providers'], \is_string(...)))
            : null;
        $workflow->createdAt = new \DateTimeImmutable();

        return $workflow;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    private static function toMap(mixed $value): array
    {
        /** @var array<string, mixed> $map */
        $map = (array) $value;

        return $map;
    }
}
