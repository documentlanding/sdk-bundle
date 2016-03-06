<?php

namespace DocumentLanding\SdkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sdk');

        $rootNode
            ->children()
               ->scalarNode('api_key')->defaultValue('ChangeThis')->end()
               ->scalarNode('lead_class')->end()
               ->scalarNode('lead_form_type')->end()
               ->scalarNode('receipt_email')->defaultValue(false)->end()
               ->scalarNode('audit')->defaultValue(true)->end()
            ->end();

        return $treeBuilder;
    }
}
