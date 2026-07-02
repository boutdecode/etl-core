<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\Notifications\Domain\Attribute;

/**
 * Marks a class as a notification provider and declares its unique code.
 *
 * Usage:
 * <code>
 * #[AsNotificationProvider(code: 'email')]
 * class EmailNotificationProvider implements NotificationProvider { … }
 * </code>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsNotificationProvider
{
    public function __construct(
        public string $code,
    ) {
    }
}
