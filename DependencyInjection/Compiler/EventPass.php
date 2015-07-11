<?php

namespace Symforce\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class EventPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $definition = $container->getDefinition('sf.compiler.event') ;

        $tagName = 'sf.event.builder' ;
        $definition->addMethodCall('setTagName', array($tagName) ) ;

        $ignore_name_list    = array( 'event', 'builder', 'compiler' ) ;

        $hash   = array() ;
        foreach ($container->findTaggedServiceIds($tagName) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }

            $_def = $container->getDefinition($id) ;

            if( !isset($attributes['alias']) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s) require alias", $id, $tagName));
            }
            $name = $attributes['alias'] ;
            if(  !\Symforce\CoreBundle\PhpHelper\PhpHelper::isClassName($name) || in_array($name, $ignore_name_list) ) {
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) alias invalid", $id, $tagName, $name) ) ;
            }
            if( isset($hash[ $name ]) ) {
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) conflict with service(%s)", $id, $tagName, $name, $hash[ $name ]['id'] ) ) ;
            }
            $index  = count($hash) ;
            $hash[ $name ] = array(
                'id'    => $id ,
                'index'    => $index ,
                'name'  => $name ,
                'args'  => array() ,
                'parent'  => isset($attributes['parent']) ? $attributes['parent'] : null ,
            ) ;
            $_def->addMethodCall('setId', array($index) ) ;
            $_def->addMethodCall('setName', array($name) ) ;
            if( isset($attributes['parent']) ) {
                $_def->addMethodCall('setParentName', array($attributes['parent']) ) ;
            }

            $definition->addMethodCall('addEventBuilder', array(new Reference($id))) ;
        }

        /**
         * @fixme check loop ref
         */
        foreach($hash as $name => $object ) {
            if( $object['parent'] && !isset( $hash[ $object['parent'] ] ) ) {
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) parent not exists", $object['id'], $tagName, $name, $object['parent']) ) ;
            }
        }

        $valid_parents  = join(',', array_keys($hash) )  ;

        $tagName = 'sf.event.args_builder' ;
        foreach ($container->findTaggedServiceIds($tagName) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }
            $property_compiler = $container->getDefinition($id) ;

            if (!isset($attributes['alias']) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s) require alias", $id, $tagName ) ) ;
            }
            $name =  $attributes['alias'] ;
            if(  !\Symforce\CoreBundle\PhpHelper\PhpHelper::isPropertyName($name) ){
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) alias invalid", $id, $tagName, $name) ) ;
            }
            if (!isset($attributes['parent'])) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s) require parent", $id, $tagName, $name) ) ;
            }
            $parent_name = $attributes['parent'] ;
            if ( !isset($hash[$parent_name]) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) parent must be one of(%s)", $id, $tagName, $name, $parent_name , $valid_parents ) ) ;
            }

            if( isset($hash[ $parent_name ]['properties'][ $name ]) ){
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) conflict with service(%s)", $id, $tagName, $name, $parent_name, $hash[ $parent_name ]['properties'][ $name ] ) ) ;
            }
            $hash[ $parent_name ]['properties'][ $name ] = $id ;
            $property_compiler->addMethodCall('setName',  array($name) ) ;
            $property_compiler->addMethodCall('setEventName',  array($parent_name)) ;

            if (!isset($attributes['type'])) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) require type", $id, $tagName, $name, $parent_name) ) ;
            }
            $property_compiler->addMethodCall('setType',  array($attributes['type'])) ;

            if ( isset($attributes['value'])) {
                $property_compiler->addMethodCall('setDefaultValue',  array($attributes['value'])) ;
            }

            $definition->addMethodCall('addEventArgumentBuilder',  array(new Reference($id)) ) ;
        }

        $definition->addMethodCall('compileEvents') ;
    }

}
