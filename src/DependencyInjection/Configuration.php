<?php

declare(strict_types=1);

namespace JBSNewMedia\VisBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vis');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        /** @var NodeBuilder $children */
        $children = $rootNode->children();

        /** @var ArrayNodeDefinition $localesNode */
        $localesNode = $children->arrayNode('locales');
        $localesNode->scalarPrototype();

        /** @var ScalarNodeDefinition $defaultLocaleNode */
        $defaultLocaleNode = $children->scalarNode('default_locale');
        $defaultLocaleNode->defaultValue('en');

        return $treeBuilder;
    }
}
