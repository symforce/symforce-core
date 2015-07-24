<?php

namespace Symforce\CoreBundle\Annotation ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationBuilder  ;
use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationPropertyBuilder  ;

use Symforce\CoreBundle\PhpHelper\PhpHelper ;

class SymforceAnnotationCompiler {

    const TAG_NAME  = 'sf.annotation.builder' ;
    const _TAG_NAME  = 'sf.annotation.property_builder' ;

    private $_bootstrap = false ;
    private $_classNameCache = array() ;

    private $_ignore_name_list = array('compiler', 'builder', 'annotation', 'property' ) ;
    private $_property_types =  array('bool', 'integer', 'string', 'array', 'mixed')  ;


    private $builders = array() ;
    private $public_properties = array() ;

    /**
     * @param string $name
     * @return SymforceAnnotationBuilder
     */
    private function getBuilderByName($name) {
        return $this->builders[$name] ;
    }

    public function addAnnotationClassCompiler($id, array & $attributes) {
        if (!isset($attributes['alias']) ) {
            throw new \Exception( sprintf("service(%s, tags:{name: %s}) require tag alias", $id, self::TAG_NAME) ) ;
        }
        $name =  $attributes['alias'] ;
        if(  !PhpHelper::isClassName($name) || in_array($name, $this->_ignore_name_list) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) tag alias invalid", $id, self::TAG_NAME, $name) ) ;
        }

        if( isset($this->builders[$name]) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) conflict with service(%s)", $id, self::TAG_NAME, $name, $this->getBuilderByName($name)->getId() ) ) ;
        }

        $builder    = new SymforceAnnotationBuilder();
        $builder->setId($id);
        $builder->setName($name);

        if ( isset($attributes['parent']) ) {
            $builder->setParentName($attributes['parent']);
        }
        if (isset($attributes['group'])) {
            $builder->setGroupName($attributes['group']);
        }
        if (isset($attributes['target'])) {
            $builder->setTarget($attributes['target']);
        }

        if( isset($attributes['properties']) ) {
            $builder->setPublicProperties($attributes['properties']) ;
        }

        if( isset($attributes['value']) ) {
            $builder->setValue($attributes['value']) ;
        }

        $this->builders[ $name ] = $builder ;
    }

    /**
     * @param $name
     * @return SymforceAnnotationPropertyBuilder
     */
    private function getPropertyBuilderByName($name) {
        return $this->public_properties[$name] ;
    }

    public function addAnnotationPropertyCompiler($id, array & $attributes) {
        if (!isset($attributes['alias']) ) {
            throw new \Exception( sprintf("service(%s, tags:{name: %s}) require tag alias", $id, self::_TAG_NAME) ) ;
        }
        $name = $attributes['alias'] ;
        if( !PhpHelper::isPropertyName($name) || in_array($name, $this->_ignore_name_list) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) tag alias invalid", $id, self::_TAG_NAME, $name) ) ;
        }

        $builder = new SymforceAnnotationPropertyBuilder();
        if( isset($attributes['parent']) ) {
            $parent_name = $attributes['parent'] ;
            if( !isset($this->builders[$parent_name]) ) {
                throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, parent: %s}) parent must be one of (%s)",
                    $id, self::_TAG_NAME, $name, $parent_name, join(',', array_keys($this->builders)) ) ) ;
            }
            $parent =  $this->getBuilderByName($parent_name) ;
            if( $parent->hasPropertyBuilder($name) ) {
                throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, parent: %s}) parent must be one of (%s)",
                    $id, self::_TAG_NAME, $name, $parent_name, $parent->getPropertyBuilder($name)->getId() ) ) ;
            }
            $parent->addPropertyBuilder($builder) ;
        } else {
            if( isset( $this->public_properties[ $name ] ) ) {
                throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) conflict with service(%s) tag alias",
                    $id, self::_TAG_NAME, $name, $this->getPropertyBuilderByName($name)->getId() ) ) ;
            }
            $this->public_properties[ $name ] = $builder ;
        }

        $builder->setId($id) ;
        $builder->setName($name) ;

        if ( isset($attributes['type']) ) {
            if( in_array($attributes['type'], $this->_property_types ) ) {
                throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, type: %s}) type must be one of(%s)",
                    $id, self::_TAG_NAME, $name, $attributes['type'], join(',', $this->_property_types) ) ) ;
            }
            $builder->setType($attributes['type']) ;
        }

    }

    private function hasParentsLoop(SymforceAnnotationBuilder $builder, array & $parents) {
        $name   = $builder->getName() ;
        if( isset($parents[$name]) ) {
            $parents[$name] = $builder ;
            return true ;
        }
        $parents[$name] = $builder ;
        $parent_name = $builder->getParentName() ;
        if( $parent_name ) {
            return $this->hasParentsLoop( $this->getBuilderByName($parent_name),  $parents );
        }
        return false ;
    }

    public function compileAnnotations(){

        $base_parent_class  = sprintf('%s\\SymforceAbstractAnnotation', __NAMESPACE__) ;

        /**
         * @var $class_builder SymforceAnnotationBuilder
         */
        foreach($this->builders as $annotation_name => $class_builder ) {

            $_parents = array() ;
            if( $this->hasParentsLoop($class_builder, $_parents) ) {
                throw new \Exception( sprintf("services(tag:{name:%s}) parent circular dependencies: %s ! ",  self::TAG_NAME, join(',', array_map(function(SymforceAnnotationBuilder $builder){
                    return sprintf("\n\t,service(%s, tag{alias:%s%s})", $builder->getId(), $this->getName(), $builder->getParentName() ? ', parent:' . $builder->getParentName() : '' );
                }, $_parents)) ) ) ;
            }
            $_properties = $class_builder->getProperties() ;
            $_public_properties = $class_builder->getPublicProperties()  ;
            if( $_public_properties ) foreach($_public_properties as $_public_property_name ) {
                if( !isset($this->public_properties[$_public_property_name]) ) {
                    throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, properties: %s}) property(%s) must be one of (%s)",
                        $class_builder->getId(), self::TAG_NAME, $class_builder->getName(), var_export($_public_properties, 1),
                        var_export($_public_property_name, 1), join(',', array_keys($this->public_properties)) ) ) ;
                }
            }
            $value_property_name = $class_builder->getValuePropertyName() ;
            if( $value_property_name ) {
                if( $_public_properties ) {
                    $_all_properties = array_unique( array_merge( array_keys($_properties), $_public_properties) ) ;
                } else {
                    $_all_properties = $_properties ;
                }
                if( !$class_builder->hasPropertyBuilder($value_property_name) && !isset($this->public_properties[$value_property_name]) ) {
                    throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, value: %s}) value must be one of(%s)",
                        $class_builder->getId(), self::TAG_NAME, $class_builder->getName(), $value_property_name,
                        join(',', $_all_properties) ) ) ;
                }
            } else {
                if( $class_builder->getValueNotNull() ||  $class_builder->getValueAsKey() ) {
                    throw new \Exception( sprintf("service(%s, tags:{name:%s, alias:%s, value:%s}) tag.value.name is required",
                        $class_builder->getId(), self::TAG_NAME,  $class_builder->getName(), var_export($class_builder->getValue(), 1 ) )
                     ) ;
                }
            }


            $class = new \Symforce\CoreBundle\PhpHelper\PhpClass( $this->getAnnotationClassName($annotation_name) ) ;

            $parent_name = $class_builder->getParentName() ;
            if( $parent_name ) {
                $class->setParentClassName($this->getAnnotationClassName($parent_name) ) ;
            } else {
                $class->setParentClassName($base_parent_class) ;
            }

            $class->setConstant( SymforceAnnotationCache::ANNOTATION_NAME, $annotation_name) ;

            $group_name = $class_builder->getGroupName() ;
            if( $group_name ) {
                $class->setConstant( SymforceAnnotationCache::ANNOTATION_GROUP_NAME, $group_name) ;
            }

            $value_property_name = $class_builder->getValuePropertyName() ;
            if( $value_property_name ) {
                $class->setConstant( SymforceAnnotationCache::ANNOTATION_VALUE_NAME, $value_property_name) ;
            }

            if( $class_builder->getValueAsKey() ) {
                $class->setConstant( SymforceAnnotationCache::ANNOTATION_VALUE_AS_KEY, 1 ) ;
            }

            if( $class_builder->getValueNotNull() ) {
                $class->setConstant( SymforceAnnotationCache::ANNOTATION_VALUE_NOT_NULL, 1 ) ;
            }

            $doc    = sprintf(" * @Annotation") ;
            $annotation_target = $class_builder->getTarget() ;
            if( $annotation_target ) {
                $doc    .= sprintf("\n * @Target({\"") . join('","', $annotation_target) . sprintf("\"})") ;
            }
            $class->setDocblock( $doc ) ;

            $value_property_name = null ;

            /**
             * @var $property_builder SymforceAnnotationPropertyBuilder
             */
            foreach($_properties as $property_name => $property_builder) {
                $type = $property_builder->getType() ;
                if( !$type ) $type = 'string' ;
                $class->addProperty( $property_name, null, $type, false, true );
            }

            if( $_public_properties ) {
                foreach($_public_properties as $property_name ) {
                    if( !$class->hasProperty($property_name) ) {
                        $property_builder = $this->public_properties[ $property_name ] ;
                        $type = $property_builder->getType() ;
                        if( !$type ) $type = 'string' ;
                        $class->addProperty( $property_name, null, $type, false, true ) ;
                    }
                }
            }

            $class->writeCache() ;
        }
    }

    protected function getAnnotationClassName($name) {
        if( !isset($this->_classNameCache[$name]) ) {
            $this->_classNameCache[$name] = sprintf('Symforce\\Annotation\\%s', \Symforce\CoreBundle\PhpHelper\PhpHelper::camelize($name) ) ;
        }
        return $this->_classNameCache[$name] ;
    }

}