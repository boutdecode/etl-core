<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\DependencyInjection\Compiler;

use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\PipelinePersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\StepPersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Persister\WorkflowPersister;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\PipelineProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Data\Provider\WorkflowProvider;
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\PipelineFactory;
use BoutDeCode\ETLCoreBundle\Core\Domain\Factory\WorkflowFactory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\PipelineHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Data\Persister\StepHistoryPersister;
use BoutDeCode\ETLCoreBundle\Run\Domain\Factory\PipelineHistoryFactory;
use BoutDeCode\ETLCoreBundle\Run\Domain\Factory\StepHistoryFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DataInterfaceAliasPass implements CompilerPassInterface
{
    /**
     * Les interfaces du domaine que le bundle expose et dont il attend
     * une implémentation unique fournie par l'application consommatrice.
     */
    private const INTERFACES = [
        WorkflowProvider::class,
        WorkflowPersister::class,
        PipelineProvider::class,
        PipelinePersister::class,
        StepPersister::class,
        PipelineHistoryPersister::class,
        StepHistoryPersister::class,
        PipelineFactory::class,
        WorkflowFactory::class,
        PipelineHistoryFactory::class,
        StepHistoryFactory::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::INTERFACES as $interface) {
            // Si un alias ou une définition explicite existe déjà (ex. services.yaml
            // ou #[AsAlias] encore présent), on ne touche pas à la configuration.
            if ($container->hasAlias($interface) || $container->hasDefinition($interface)) {
                continue;
            }

            $found = null;
            foreach ($container->getDefinitions() as $id => $definition) {
                try {
                    $className = $definition->getClass() ?? $id;
                    if (! class_exists($className) && ! interface_exists($className)) {
                        continue;
                    }
                    /** @var class-string $className */
                    $refClass = new \ReflectionClass($className);
                    if ($refClass->implementsInterface($interface)) {
                        $found = $id;
                        break;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }

            if ($found !== null) {
                $container->setAlias($interface, $found)->setPublic(false);
            }
        }
    }
}
