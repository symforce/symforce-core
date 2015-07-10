<?php

namespace Symforce\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AssetsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $definition = $container->getDefinition('sf.assets.factory') ;

        $tagName = 'sf.assets.resources' ;
        $definition->addMethodCall('setTagName', array($tagName) ) ;

        foreach ($container->findTaggedServiceIds($tagName) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }

            $_def = $container->getDefinition($id) ;

            if( !isset($attributes['path']) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s) require path", $id, $tagName));
            }
            $_def->addMethodCall('setPath', array($attributes['path']) );

            if( !isset($attributes['target']) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s) require target", $id, $tagName));
            }
            $_def->addMethodCall('setTarget', array($attributes['target']) );

            if( isset($attributes['extension']) ) {
                $_def->addMethodCall('setExtension', array($attributes['extension']) ) ;
            }


            $definition->addMethodCall('addAssetsResource', array($id, new Reference($id))) ;
        }


    }

}
