<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle;

use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Command\CommandHandler;
use BoutDeCode\ETLCoreBundle\CQS\Application\Operation\Query\QueryHandler;
use BoutDeCode\ETLCoreBundle\DependencyInjection\Compiler\DataInterfaceAliasPass;
use BoutDeCode\ETLCoreBundle\DependencyInjection\Compiler\ExecutableStepTagPass;
use BoutDeCode\ETLCoreBundle\DependencyInjection\ETLCoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BoutDeCodeETLCoreBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new ETLCoreExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(QueryHandler::class)
            ->addTag('messenger.message_handler', [
                'bus' => 'boutdecode_etl_core.query.bus',
            ]);

        $container->registerForAutoconfiguration(CommandHandler::class)
            ->addTag('messenger.message_handler', [
                'bus' => 'boutdecode_etl_core.command.bus',
            ]);

        $container->addCompilerPass(new DataInterfaceAliasPass());
        $container->addCompilerPass(new ExecutableStepTagPass());
    }
}
