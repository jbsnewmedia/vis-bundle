<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vis');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('locales')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('default_locale')->defaultValue('en')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
