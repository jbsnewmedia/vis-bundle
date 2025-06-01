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

        // Define the configuration structure here
        // $rootNode
        //     ->children()
        //         ->scalarNode('some_option')->defaultValue('default_value')->end()
        //     ->end()
        // ;

        return $treeBuilder;
    }
}
