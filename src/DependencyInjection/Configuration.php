<?php

declare(strict_types=1);

namespace BoutDeCode\ETLCoreBundle\DependencyInjection;

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
            ->end();

        return $treeBuilder;
    }
}
