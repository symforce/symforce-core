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

        $definition = $container->getDefinition('sf.compiler.annotation') ;

        $ignore_name_list   = array('compiler', 'builder', 'annotation', 'property' ) ;

        $tagName = 'sf.annotation.builder' ;
        $hash   = array() ;
        foreach ($container->findTaggedServiceIds($tagName) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }
            $class_compiler = $container->getDefinition($id) ;
            if (!isset($attributes['alias']) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s) require alias", $id, $tagName ) ) ;
            }
            $name =  $attributes['alias'] ;
            if(  !\Symforce\CoreBundle\PhpHelper\PhpHelper::isClassName($name) || in_array($name, $ignore_name_list) ) {
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) alias invalid", $id, $tagName, $name) ) ;
            }
            if( isset($hash[ $name ]) ) {
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) conflict with service(%s)", $id, $tagName, $name, $hash[ $name ]['id'] ) ) ;
            }
            $hash[ $name ] = array(
                'name'  => $name ,
                'id'  => $id ,
                'parent'  => isset($attributes['parent']) ? $attributes['parent'] : null ,
                'properties'  => array() ,
                'value_property_name'  => null ,
            ) ;
            $class_compiler->addMethodCall('setName', array($name) ) ;

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

        /**
         * @fixme check loop ref
         */
        foreach($hash as $name => $object ) {
            if( $object['parent'] && !isset( $hash[ $object['parent']  ] ) ) {
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) parent not exists", $object['id'], $tagName, $name, $object['parent']) ) ;
            }
        }

        $valid_parents  = join(',', array_keys($hash) )  ;
        $valid_types  = array('bool', 'integer', 'string', 'array', 'mixed')  ;

        $tagName = 'sf.annotation.property_builder' ;
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
                throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) conflict with service(%s)", $id, $tagName, $name, $hash[ $parent_name ]['properties'][ $name ] ) ) ;
            }
            $hash[ $parent_name ]['properties'][ $name ] = $id ;

            $property_compiler->addMethodCall('setName',  array($name) ) ;
            $property_compiler->addMethodCall('setAnnotationName',  array($parent_name)) ;

            if ( isset($attributes['type']) ) {
                if( in_array($attributes['type'], $valid_types ) ) {
                    throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s, type=%s) type must be one of(%s)", $id, $tagName, $name, $parent_name, $attributes['type'], join(',', $valid_types) ) ) ;
                } else {
                    $property_compiler->addMethodCall('setType',  array($attributes['type']) ) ;
                }
            }

            if (isset($attributes['value'])) {
                if( null !== $hash[ $parent_name ]['value_property_name'] ) {
                    $_value_property_name = $hash[ $parent_name ]['value_property_name']  ;
                    throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) conflict with service(%s) value", $id, $tagName, $name , $parent_name ,
                        $hash[ $parent_name ]['properties'][ $_value_property_name ] ) ) ;
                }
                $class_maps[ $attributes['parent'] ]['value_property_name'] = $attributes['alias'] ;
                $property_compiler->addMethodCall('setIsValueProperty',  array(true)) ;
            }

            $definition->addMethodCall('addAnnotationPropertyCompiler',  array(new Reference($id)) ) ;
        }

        $definition->addMethodCall('compileAnnotations') ;

    }

}
