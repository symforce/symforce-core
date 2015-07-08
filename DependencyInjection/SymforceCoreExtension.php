<?php

namespace Symforce\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

use Symfony\Component\Yaml\Parser as YamlParser ;

class SymforceCoreExtension extends Extension
{
    /**
     *
     * @var YamlParser 
     */
    private $yamlParser ;
    
    public function load(array $configs, ContainerBuilder $container)
    {

    }

    public function getAlias()
    {
        return 'symforce_core' ;
    }
}
