<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\DependencyInjection\Compiler;

use BoutDeCode\ETLCoreBundle\ETL\Domain\Attribute\AsExecutableStep;
use BoutDeCode\ETLCoreBundle\ETL\Domain\Model\ExecutableStep;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tags every service whose class implements {@see ExecutableStep}
 * with the 'boutdecode_etl_core.executable_step' tag.
 *
 * When the class carries a {@see AsExecutableStep} PHP attribute the tag is enriched
 * with a `code` attribute (the step unique identifier), making it available for
 * container-level inspection without instantiating the service.
 *
 * This replaces the equivalent _instanceof rule previously defined in services.yaml.
 */
final class ExecutableStepTagPass implements CompilerPassInterface
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

            if (! $refClass->implementsInterface(ExecutableStep::class)) {
                continue;
            }

            if ($definition->hasTag('boutdecode_etl_core.executable_step')) {
                continue;
            }

            $tagAttributes = [];

            $phpAttributes = $refClass->getAttributes(AsExecutableStep::class);
            if ($phpAttributes !== []) {
                /** @var AsExecutableStep $stepAttribute */
                $stepAttribute = $phpAttributes[0]->newInstance();
                $tagAttributes['code'] = $stepAttribute->code;
            }

            $definition->addTag('boutdecode_etl_core.executable_step', $tagAttributes);
        }
    }
}
