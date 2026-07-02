<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Notifications\Domain\Resolver;

use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationProvider;

class DefaultNotificationProviderResolver implements NotificationProviderResolver
{
    /**
     * @var NotificationProvider[]
     */
    private array $providers = [];

    /**
     * @param iterable<NotificationProvider> $providers
     */
    public function __construct(
        iterable $providers = [],
    ) {
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    public function addProvider(NotificationProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    public function resolve(string $code): ?NotificationProvider
    {
        foreach ($this->providers as $provider) {
            if ($provider->getCode() === $code) {
                return $provider;
            }
        }

        return null;
    }

    public function list(): array
    {
        return $this->providers;
    }
}
