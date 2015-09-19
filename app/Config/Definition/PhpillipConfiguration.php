<?php

namespace Tom32i\Phpillip\Config\Definition;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Phillip configuration
 */
class PhpillipConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('phpillip');

        $rootNode
            ->children()
                ->scalarNode('route_class')
                    ->defaultValue('Tom32i\Phpillip\Routing\Route')
                ->end()
                ->scalarNode('sitemap')
                    ->defaultTrue()
                ->end()
                ->variableNode('parameters')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
