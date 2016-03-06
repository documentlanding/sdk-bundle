<?php

namespace DocumentLanding\SdkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class SDKExtension extends Extension
{

    /**
     * {@inheritDoc}
     */	
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor     = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);        
        $container->setParameter('DocumentLandingSdkBundleConfig', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load('../Resources/config/services.yml'); 
    }

}
