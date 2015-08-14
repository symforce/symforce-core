<?php

namespace Symforce\CoreBundle\Annotation ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationTypeBuilder ;
use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationTreeBuilder ;
use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationClassBuilder ;
use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationPropertyBuilder  ;

use Symforce\CoreBundle\PhpHelper\PhpHelper ;

class SymforceAnnotationCompiler {

    const TYPE_TAG_NAME  = 'sf.annotation.type_builder' ;
    const TREE_TAG_NAME  = 'sf.annotation.tree_builder' ;
    const CLASS_TAG_NAME  = 'sf.annotation.class_builder' ;
    const PROPERTY_TAG_NAME  = 'sf.annotation.property_builder' ;

    private $_bootstrap = false ;
    private $_classNameCache = array() ;

    private $_ignore_name_list  = array('compiler', 'annotation') ;
    private $_default_property_types    = array('bool', 'integer', 'string', 'array', 'mixed')  ;


    private $type_builders = array() ;
    private $class_builders = array() ;
    private $public_properties = array() ;

    /**
     * @param string $name
     * @return SymforceAnnotationClassBuilder
     */
    private function getClassBuilderByName($name) {
        return $this->class_builders[$name] ;
    }

    /**
     * @param string $name
     * @return SymforceAnnotationTypeBuilder
     */
    private function getTypeBuilderByName($name) {
        return $this->type_builders[$name] ;
    }


    public function addAnnotationTypeCompiler($id, array & $attributes) {
        if (!isset($attributes['alias']) ) {
            throw new \Exception( sprintf("service(%s, tags:{name: %s}) require tag alias", $id, self::TYPE_TAG_NAME) ) ;
        }
        $name =  $attributes['alias'] ;
        if( in_array($name, $this->_default_property_types) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) tag alias can not be default type(%s)", $id, self::TYPE_TAG_NAME, $name, join(',', $this->_default_property_types)) ) ;
        }
        $camelize_name  = PhpHelper::camelize($name) ;
        if( !PhpHelper::isClassName($camelize_name) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) tag alias invalid", $id, self::TYPE_TAG_NAME, $name) ) ;
        }
        if( isset($this->type_builders[$name]) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) conflict with service(%s)", $id, self::TYPE_TAG_NAME, $name, $this->getTypeBuilderByName($name)->getId() ) ) ;
        }

        $builder    = new SymforceAnnotationTypeBuilder();
        $builder->setId($id);
        $builder->setName($name);
        $builder->setCamelizeName($camelize_name);
        $this->type_builders[$name] = $builder ;

        if ( !isset($attributes['class']) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) require tag class", $id, self::TYPE_TAG_NAME, $name) ) ;
        }

        if ( isset($attributes['class']) ) {
            if( !class_exists($attributes['class']) ){
                throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s, class: %s}) tag class is not a valid php class name", $id, self::TYPE_TAG_NAME, $name, $attributes['class'] ) ) ;
            }
            $builder->setClassName($attributes['class']);
        }

    }

    public function addAnnotationClassCompiler($id, array & $attributes) {
        if (!isset($attributes['alias']) ) {
            throw new \Exception( sprintf("service(%s, tags:{name: %s}) require tag alias", $id, self::CLASS_TAG_NAME) ) ;
        }
        $name =  $attributes['alias'] ;
        $camelize_name  = PhpHelper::camelize($name) ;
        if(  !PhpHelper::isClassName($camelize_name) || in_array($name, $this->_ignore_name_list) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) tag alias invalid", $id, self::CLASS_TAG_NAME, $name) ) ;
        }

        if( isset($this->class_builders[$name]) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) conflict with service(%s)", $id, self::CLASS_TAG_NAME, $name, $this->getClassBuilderByName($name)->getId() ) ) ;
        }

        $builder    = new SymforceAnnotationClassBuilder();
        $builder->setId($id);
        $builder->setName($name);
        $builder->setCamelizeName($camelize_name);

        if ( isset($attributes['parent']) ) {
            $builder->setParentName($attributes['parent']);
        }
        if (isset($attributes['group'])) {
            $builder->setGroupId( (int) $attributes['group'] );
        }
        if (isset($attributes['target'])) {
            $builder->setTarget($attributes['target']);
        }

        if( isset($attributes['properties']) ) {
            $_properties = $builder->setPublicProperties($attributes['properties']) ;
            if( !empty($_properties) ) {
                foreach($_properties as $_property_name => $_property_type ) {
                    if( !PhpHelper::isPropertyName($_property_name) || in_array($_property_name, $this->_ignore_name_list) ) {
                        throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s }) properties(name:%s, type:%s)  name invalid", $id, self::CLASS_TAG_NAME, $name, $_property_name, $_property_type ) ) ;
                    }
                    $_property_builder = new SymforceAnnotationPropertyBuilder();
                    $_property_builder->setId($id) ;
                    $_property_builder->setName($_property_name) ;
                    $builder->addPropertyBuilder($_property_builder) ;
                    if( !in_array($_property_type, $this->_default_property_types ) ) {
                        throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s}) properties(name:%s, type:%s) type must be one of(%s)",
                            $id, self::CLASS_TAG_NAME, $name, $_property_name, $_property_type, join(',', $this->_default_property_types) ) ) ;
                    }
                    $_property_builder->setType($_property_type) ;
                }
            }
        }

        if( isset($attributes['value']) ) {
            $builder->setValuePropertyName($attributes['value']) ;
        }
        if( isset($attributes['as_key']) ) {
            $builder->setValueAsKey($attributes['as_key']) ;
        }
        if( isset($attributes['not_null']) ) {
            $builder->setValueNotNull($attributes['not_null']) ;
        }

        $this->class_builders[ $name ] = $builder ;
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
            throw new \Exception( sprintf("service(%s, tags:{name: %s}) require tag alias", $id, self::PROPERTY_TAG_NAME) ) ;
        }
        $name = $attributes['alias'] ;
        if( !PhpHelper::isPropertyName($name) || in_array($name, $this->_ignore_name_list) ) {
            throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) tag alias invalid", $id, self::PROPERTY_TAG_NAME, $name) ) ;
        }

        $builder = new SymforceAnnotationPropertyBuilder();
        $builder->setId($id) ;
        $builder->setName($name) ;


        if( isset($attributes['parent']) ) {
            $parent_name = $attributes['parent'] ;
            if( !isset($this->class_builders[$parent_name]) ) {
                throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, parent: %s}) parent must be one of (%s)",
                    $id, self::PROPERTY_TAG_NAME, $name, $parent_name, join(',', array_keys($this->class_builders)) ) ) ;
            }
            $parent =  $this->getClassBuilderByName($parent_name) ;
            if( $parent->hasPropertyBuilder($name) ) {
                throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, parent: %s}) parent must be one of (%s)",
                    $id, self::PROPERTY_TAG_NAME, $name, $parent_name, $parent->getPropertyBuilder($name)->getId() ) ) ;
            }
            $parent->addPropertyBuilder($builder) ;
        } else {
            if( isset( $this->public_properties[ $name ] ) ) {
                throw new \Exception(sprintf("service(%s, tags:{name: %s, alias: %s}) conflict with service(%s) tag alias",
                    $id, self::PROPERTY_TAG_NAME, $name, $this->getPropertyBuilderByName($name)->getId() ) ) ;
            }
            $this->public_properties[ $name ] = $builder ;
        }

        if ( isset($attributes['type']) ) {
            if( !in_array($attributes['type'], $this->_default_property_types ) ) {
                throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, type: %s}) type must be one of(%s)",
                    $id, self::PROPERTY_TAG_NAME, $name, $attributes['type'], join(',', $this->_default_property_types) ) ) ;
            }
            $builder->setType($attributes['type']) ;
        }

    }

    private function hasParentsLoop(SymforceAnnotationClassBuilder $builder, array & $parents) {
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

        $cache_class = new \Symforce\CoreBundle\PhpHelper\PhpClass() ;
        $cache_class
            ->setName('Symforce\\Builder\\SymforceAnnotationCache')
            ->setFinal(true)
            ->setParentClassName('Symforce\\CoreBundle\\Annotation\\SymforceAnnotationCache')
        ;

        $base_parent_class  = sprintf('%s\\SymforceAbstractAnnotation', __NAMESPACE__) ;

        /**
         * @var $class_builder SymforceAnnotationClassBuilder
         */
        foreach($this->class_builders as $annotation_name => $class_builder ) {

            $_parents = array() ;
            if( $this->hasParentsLoop($class_builder, $_parents) ) {
                throw new \Exception( sprintf("services(tag:{name:%s}) parent circular dependencies: %s ! ",  self::CLASS_TAG_NAME, join(',', array_map(function(SymforceAnnotationBuilder $builder){
                    return sprintf("\n\t,service(%s, tag{alias:%s%s})", $class_builder->getId(), $class_builder->getName(), $class_builder->getParentName() ? ', parent:' . $class_builder->getParentName() : '' );
                }, $_parents)) ) ) ;
            }
            $_properties = $class_builder->getProperties() ;
            $_public_properties = $class_builder->getPublicProperties()  ;
            if( $_public_properties ) foreach($_public_properties as $_public_property_name ) {
                if( !isset($this->public_properties[$_public_property_name]) ) {
                    throw new \Exception( sprintf("service(%s, tags:{name: %s, alias: %s, properties: %s}) property(%s) must be one of (%s)",
                        $class_builder->getId(), self::CLASS_TAG_NAME, $class_builder->getName(), var_export($_public_properties, 1),
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
                        $class_builder->getId(), self::CLASS_TAG_NAME, $class_builder->getName(), $value_property_name,
                        join(',', $_all_properties) ) ) ;
                }
            } else {
                if( $class_builder->getValueNotNull() ||  $class_builder->getValueAsKey() ) {
                    throw new \Exception( sprintf("service(%s, tags:{name:%s, alias:%s}) tag.value is required",
                        $class_builder->getId(), self::CLASS_TAG_NAME,  $class_builder->getName() )
                     ) ;
                }
            }

            $annotation_camelize_name  = $class_builder->getCamelizeName() ;
            $class = new \Symforce\CoreBundle\PhpHelper\PhpClass( $this->getAnnotationClassName($annotation_camelize_name) ) ;

            $parent_name = $class_builder->getParentName() ;
            if( $parent_name ) {
                $class->setParentClassName($this->getAnnotationClassName($parent_name) ) ;
            } else {
                $class->setParentClassName($base_parent_class) ;
            }

            $class->setConstant( 'SYMFORCE_ANNOTATION_NAME', $annotation_name) ;

            $group_id = $class_builder->getGroupId() ;
            if( null !== $group_id ) {
                $class->setConstant( 'SYMFORCE_ANNOTATION_GROUP_ID', $group_id) ;
            }

            $value_property_name = $class_builder->getValuePropertyName() ;
            if( $value_property_name ) {
                $class->setConstant('SYMFORCE_ANNOTATION_VALUE_AS_PROPERTY', $value_property_name) ;
            }

            if( $class_builder->getValueAsKey() ) {
                $class->setConstant('SYMFORCE_ANNOTATION_VALUE_AS_KEY', 1 ) ;
            }

            if( $class_builder->getValueNotNull() ) {
                $class->setConstant('SYMFORCE_ANNOTATION_VALUE_NOT_NULL', 1 ) ;
            }

            $doc    = sprintf(" * @Annotation") ;
            $annotation_target = $class_builder->getTarget() ;
            if( !$annotation_target ) $annotation_target = array('CLASS', 'PROPERTY');
            $doc    .= sprintf("\n * @Target({\"") . join('","', $annotation_target) . sprintf("\"})") ;
            $class->setDocblock( $doc ) ;

            if( in_array('CLASS', $annotation_target) ) {
                $cache_class
                    ->addMethod('get' . $annotation_camelize_name . 'ClassAnnotation'  , true)
                    ->setDocblock('/* @return \\' . $class->getName() . ' */')
                    ->getWriter()
                    ->writeln( sprintf('return $this->getClassValue("%s");', $class_builder->getName() ))
                ;
                if( $class_builder->getValueAsKey() ) {
                    $cache_class
                        ->addMethod('get' . $annotation_camelize_name . 'ClassValues'  , true)
                        ->setDocblock('/* @return array(\\' . $class->getName() . ') */')
                        ->getWriter()
                        ->writeln( sprintf('return $this->getClassValue("%s", true);', $class_builder->getName() ))
                    ;
                }
            }

            if( in_array('PROPERTY', $annotation_target) ) {
                $cache_class
                    ->addMethod('get' . $annotation_camelize_name . 'PropertyAnnotation'  , true)
                    ->addParameter( \CG\Generator\PhpParameter::create('property_name') )
                    ->setDocblock('/* @return \\' . $class->getName() . ' */')
                    ->getWriter()
                    ->writeln( sprintf('return $this->getPropertyValue($property_name, "%s");', $class_builder->getName() ))
                ;

                if( $class_builder->getValueAsKey() ) {
                    $cache_class
                        ->addMethod('get' . $annotation_camelize_name . 'PropertyValues'  , true)
                        ->addParameter( \CG\Generator\PhpParameter::create('property_name') )
                        ->setDocblock('/* @return array(\\' . $class->getName() . ') */')
                        ->getWriter()
                        ->writeln( sprintf('return $this->getPropertyValue($property_name, "%s", true);', $class_builder->getName() ))
                    ;
                }
            }

            $value_property_name = null ;

            /**
             * @var $property_builder SymforceAnnotationPropertyBuilder
             */

            if( $_public_properties ) {
                foreach($_public_properties as $property_name ) {
                    if( ! isset($_properties[$property_name]) ) {
                        $property_builder = $this->public_properties[ $property_name ] ;
                        $type = $property_builder->getType() ;
                        if( !$type ) $type = 'string' ;
                        $class->addProperty( $property_name, null, $type, false, true ) ;
                    }
                }
            }

            foreach($_properties as $property_name => $property_builder) {
                $type = $property_builder->getType() ;
                if( !$type ) $type = 'string' ;
                $class->addProperty( $property_name, null, $type, false, true );
            }


            $class->writeCache() ;

        }

        $cache_class->writeCache() ;
    }

    protected function getAnnotationClassName($name) {
        if( !isset($this->_classNameCache[$name]) ) {
            $this->_classNameCache[$name] = sprintf('Symforce\\Annotation\\%s',$name ) ;
        }
        return $this->_classNameCache[$name] ;
    }

}