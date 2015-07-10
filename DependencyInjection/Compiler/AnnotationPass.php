<?php

namespace Symforce\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AnnotationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $definition = $container->getDefinition('sf.annotation.factory') ;
        
        $tagName = 'sf.annotation.class_compiler' ;
        $class_maps = array() ;
        foreach ($container->findTaggedServiceIds($tagName) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }
            $class_compiler = $container->getDefinition($id) ;
            if (!isset($attributes['alias']) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s) require alias", $id, $tagName ) ) ;
            }
            if(  !\Symforce\CoreBundle\PhpHelper\PhpHelper::isIdentifier($attributes['alias']) ){
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) alias invalid", $id, $tagName, $attributes['alias']) ) ;
            }
            $class_compiler->addMethodCall('setName', array($attributes['alias']) ) ;

            $class_maps[ $attributes['alias'] ] = array(
                'name'  => $attributes['alias'] ,
                'id'  => $id ,
                'parent'  => isset($attributes['parent']) ? $attributes['parent'] : null ,
                'properties'  => array() ,
                'value_property_name'  => null ,
            ) ;

            if ( isset($attributes['parent']) ) {
                $class_compiler->addMethodCall('setParentAnnotationName', array($attributes['parent']) ) ;
            }
            if (isset($attributes['group'])) {
                $class_compiler->addMethodCall('setAnnotationGroupName', array($attributes['group']) ) ;
            }
            if (isset($attributes['target'])) {
                $class_compiler->addMethodCall('setAnnotationTarget', array($attributes['target']) ) ;
            }
            $definition->addMethodCall('addAnnotationClassCompiler', array(new Reference($id)));
        }

        foreach($class_maps as $class_name => $class_object ) {
            if( $class_object['parent'] && !isset( $class_maps[ $class_object['parent']  ] ) ) {
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) parent not exists", $class_object['id'], $tagName, $class_object['parent'], $attributes['alias']) ) ;
            }
        }

        $valid_parents  = join(',', array_keys($class_maps) )  ;
        $valid_types  = array('bool', 'integer', 'string', 'array', 'mixed')  ;

        $tagName = 'sf.annotation.property_compiler' ;
        foreach ($container->findTaggedServiceIds($tagName) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }
            $property_compiler = $container->getDefinition($id) ;

            if (!isset($attributes['alias']) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s) require alias", $id, $tagName ) ) ;
            }

            if(  !\Symforce\CoreBundle\PhpHelper\PhpHelper::isIdentifier($attributes['alias']) ){
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) alias invalid", $id, $tagName, $attributes['alias']) ) ;
            }

            if( isset($class_maps[ $attributes['parent'] ]['properties'][ $attributes['alias']  ]) ){
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) conflict with service(%s)", $id, $tagName, $attributes['alias'], $class_maps[ $attributes['parent'] ]['properties'][ $attributes['alias'] ] ) ) ;
            }
            $class_maps[ $attributes['parent'] ]['properties'][ $attributes['alias']  ] = $id ;

            $property_compiler->addMethodCall('setName',  array($attributes['alias']) ) ;

            if ( isset($attributes['type']) ) {
                if( in_array($attributes['type'], $valid_types ) ) {
                    throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s, type=%s) type must be one of(%s)", $id, $tagName, $attributes['alias'], $attributes['type'], join(',', $valid_types) ) ) ;
                } else {
                    $property_compiler->addMethodCall('setType',  array($attributes['type']) ) ;
                }
            }

            if (!isset($attributes['parent'])) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s) require parent", $id, $tagName, $attributes['alias']) ) ;
            }
            if ( !isset($class_maps[ $attributes['parent'] ]) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) parent must be one of(%s)", $id, $tagName, $attributes['alias'], $attributes['parent'] , $valid_parents ) ) ;
            }
            $property_compiler->addMethodCall('setAnnotationName',  array($attributes['parent'])) ;

            if (isset($attributes['value'])) {
                if( null !== $class_maps[ $attributes['parent'] ]['value_property_name'] ) {
                    $_value_property_name = $class_maps[ $attributes['parent'] ]['value_property_name']  ;
                    throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) conflict with service(%s) value", $id, $tagName, $attributes['alias'], $attributes['parent'] ,
                        $class_maps[ $attributes['parent'] ]['properties'][ $_value_property_name ] ) ) ;
                }
                $class_maps[ $attributes['parent'] ]['value_property_name'] = $attributes['alias'] ;
                $property_compiler->addMethodCall('setIsValueProperty',  array(true)) ;
            }

            $definition->addMethodCall('addAnnotationPropertyCompiler',  array(new Reference($id)) ) ;
        }

        $definition->addMethodCall('buildAnnotations') ;

    }

}
