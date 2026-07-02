<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Notifications\Domain\Resolver;

use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationProvider;

interface NotificationProviderResolver
{
    public function resolve(string $code): ?NotificationProvider;

    /**
     * @return array<NotificationProvider>
     */
    public function list(): array;
}
