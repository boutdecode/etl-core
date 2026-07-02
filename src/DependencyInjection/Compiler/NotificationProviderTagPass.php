<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\DependencyInjection\Compiler;

use BoutDeCode\ETLCoreBundle\Notifications\Domain\Attribute\AsNotificationProvider;
use BoutDeCode\ETLCoreBundle\Notifications\Domain\Model\NotificationProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tags every service whose class implements {@see NotificationProvider}
 * with the 'boutdecode_etl_core.notification_provider' tag.
 *
 * When the class carries a {@see AsNotificationProvider} PHP attribute the tag is enriched
 * with a `code` attribute (the provider unique identifier), making it available for
 * container-level inspection without instantiating the service.
 */
final class NotificationProviderTagPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass() ?? $id;

            try {
                if (! class_exists($class) && ! interface_exists($class)) {
                    continue;
                }
                /** @var class-string $class */
                $refClass = new \ReflectionClass($class);
            } catch (\Throwable) {
                continue;
            }

            if (! $refClass->implementsInterface(NotificationProvider::class)) {
                continue;
            }

            if ($definition->hasTag('boutdecode_etl_core.notification_provider')) {
                continue;
            }

            $tagAttributes = [];

            $phpAttributes = $refClass->getAttributes(AsNotificationProvider::class);
            if ($phpAttributes !== []) {
                /** @var AsNotificationProvider $providerAttribute */
                $providerAttribute = $phpAttributes[0]->newInstance();
                $tagAttributes['code'] = $providerAttribute->code;
            }

            $definition->addTag('boutdecode_etl_core.notification_provider', $tagAttributes);
        }
    }
}
