<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\DependencyInjection;

use Cron\CronExpression;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('boutdecode_etl_core');

        $treeBuilder->getRootNode()
            ->children()
            ->arrayNode('notifications')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('email')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('from')->defaultValue('noreply@example.com')->end()
            ->arrayNode('to')
            ->scalarPrototype()->end()
            ->defaultValue([])
            ->end()
            ->scalarNode('subject_prefix')->defaultValue('[ETL]')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('purge')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->integerNode('retention_days')->defaultValue(30)->min(1)->end()
            ->scalarNode('cron_expression')
            ->defaultValue('0 3 * * *')
            ->validate()
            ->ifTrue(static fn (string $value): bool => ! CronExpression::isValidExpression($value))
            ->thenInvalid('Invalid purge cron expression: %s')
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
