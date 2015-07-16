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
                throw new \Exception( sprintf("service(%s, tags:{name: %s}) require tag alias", $id, $tagName ) ) ;
            }
            $name =  $attributes['alias'] ;
            if(  !\Symforce\CoreBundle\PhpHelper\PhpHelper::isClassName($name) || in_array($name, $ignore_name_list) ) {
                throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) tag alias invalid", $id, $tagName, $name) ) ;
            }
            if( isset($hash[ $name ]) ) {
                throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) conflict with service(%s)", $id, $tagName, $name, $hash[ $name ]['id'] ) ) ;
            }
            $hash[ $name ] = array(
                'name'  => $name ,
                'id'  => $id ,
                'parent'  => isset($attributes['parent']) ? $attributes['parent'] : null ,

                'properties'  => array() ,
                'public_properties' =>  isset($attributes['properties']) ? $attributes['properties'] : null ,

                'value'  => isset($attributes['value']) ? $attributes['value'] : null ,
                'value_as_key'  => isset($attributes['value_as_key']) ? $attributes['value_as_key'] : null ,
                'value_not_null'  => isset($attributes['value_not_null']) ? $attributes['value_not_null'] : null ,
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

        $properties  = array() ;

        $_tagName = $tagName ;
        $tagName  = 'sf.annotation.property_builder' ;
        foreach ($container->findTaggedServiceIds($tagName) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }
            $property_compiler = $container->getDefinition($id) ;

            if (!isset($attributes['alias']) ) {
                throw new \Exception( sprintf("service(%s, tags:{name: %s}) require tag alias", $id, $tagName ) ) ;
            }
            $name =  $attributes['alias'] ;
            if(  !\Symforce\CoreBundle\PhpHelper\PhpHelper::isPropertyName($name) ){
                throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) tag alias invalid", $id, $tagName, $name) ) ;
            }
            $property_compiler->addMethodCall('setName',  array($name) ) ;
            if ( isset($attributes['type']) ) {
                if( in_array($attributes['type'], $valid_types ) ) {
                    throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, type: %s}) type must be one of(%s)", $id, $tagName, $name, $attributes['type'], join(',', $valid_types) ) ) ;
                } else {
                    $property_compiler->addMethodCall('setType',  array($attributes['type']) ) ;
                }
            }

            if ( !isset($attributes['parent']) ) {
                if( isset($properties[ $name ]) ) {
                    throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s}) conflict with service(%s)", $id, $tagName, $name, $properties[ $name ] ) ) ;
                }
                $properties[ $name ] = $id ;
            } else {
                $parent_name = $attributes['parent'] ;
                if ( !isset($hash[$parent_name]) ) {
                    throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, parent: %s}) tag parent must be one of(%s)", $id, $tagName, $name, $parent_name , $valid_parents ) ) ;
                }
                if( isset($hash[ $parent_name ]['properties'][ $name ]) ){
                    throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) conflict with service(%s) tag alias", $id, $tagName, $name, $hash[ $parent_name ]['properties'][ $name ] ) ) ;
                }
                $hash[ $parent_name ]['properties'][ $name ] = $id ;
                $property_compiler->addMethodCall('setAnnotationName',  array($parent_name)) ;
            }
            $definition->addMethodCall('addAnnotationPropertyCompiler',  array(new Reference($id)) ) ;
        }

        foreach($hash as $name => $object ) {
            $_public_properties = $object['public_properties'] ;
            if( $_public_properties ) {
                if( !is_array($_public_properties) ) {
                    $_public_properties = preg_split('/\s*,\s*/', trim($_public_properties) );
                }
                foreach($_public_properties as $property_name ) {
                    if( !is_string($property_name) || !isset($properties[$property_name]) ) {
                        throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, properties: %s}) property(%s) must be one of (%s)",
                            $object['id'], $_tagName, $name, var_export($object['public_properties'], 1),
                            var_export($property_name, 1), join(',', array_keys($properties)) ) ) ;
                    }
                }
                $definition->addMethodCall('addAnnotationProperties',  array($name, $_public_properties) ) ;
            } else {
                $_public_properties = array() ;
            }

            if( $object['value']  ) {
                $property_name =  $object['value'] ;
                if( !isset($object['properties'][$property_name]) && !in_array($property_name, $_public_properties) ) {
                    $_all_properties = array_unique( array_merge( array_keys($object['properties']), $_public_properties) ) ;
                    throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, value: %s}) value must be one of(%s)",
                        $object['id'], $_tagName, $name, $property_name,
                        join(',', $_all_properties) ) ) ;
                }
                $class_compiler = $container->getDefinition($object['id']) ;
                $class_compiler->addMethodCall('setAnnotationValuePropertyName',  array($object['value']) ) ;
            }

            if( $object['value_not_null'] ) {
                if( !$object['value'] ) {
                    throw new \Exception( sprintf("service(%s, tags:{name:%s, alias:%s, value_as_key:%s}) tag.value_not_null require value property",
                        $object['id'], $_tagName, $name, json_encode($object['value_not_null'])
                    ) ) ;
                }

                $class_compiler = $container->getDefinition($object['id']) ;
                $class_compiler->addMethodCall('setAnnotationValueNotNull', array(true) ) ;
            }

            if( $object['value_as_key'] ) {
                if( !$object['value_property_service_name'] ) {
                    throw new \Exception( sprintf("service(%s, tags:{name:%s, alias:%s, value_as_key:%s}) tag.value_as_key require value property",
                        $object['id'], $_tagName, $name, json_encode($object['value_as_key'])
                    ) ) ;
                }
                $class_compiler = $container->getDefinition($object['id']) ;
                $class_compiler->addMethodCall('setAnnotationValueAsKey', array(true) ) ;
            }
        }

        $definition->addMethodCall('compileAnnotations') ;

    }

}
