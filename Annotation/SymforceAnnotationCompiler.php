<?php

namespace Symforce\CoreBundle\Annotation ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationBuilder  ;
use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationPropertyBuilder  ;

class SymforceAnnotationCompiler {

    private $_bootstrap = false ;

    private $_classNameCache = array() ;

    private $builders = array() ;

    private $properties_objects = array() ;
    private $public_properties_objects = array() ;

    private $public_properties = array() ;

    public function addAnnotationClassCompiler(SymforceAnnotationBuilder  $builder) {
        if( $this->_bootstrap ) {
            return ;
        }
        $name = $builder->getName() ;
        $this->builders[ $name ] = $builder ;
    }

    public function addAnnotationPropertyCompiler(SymforceAnnotationPropertyBuilder  $builder) {
        if( $this->_bootstrap ) {
            return ;
        }
        $name = $builder->getAnnotationName() ;
        $property_name = $builder->getName() ;
        if( $name ) {
            $this->properties_objects[ $name ][ $property_name ] = $builder ;
        } else {
            $this->public_properties_objects[ $property_name ] = $builder ;
        }
    }

    public function addAnnotationProperties($annotation_name, array $properties) {
        if( $this->_bootstrap ) {
            return ;
        }
        $this->public_properties[ $annotation_name ] = $properties ;
    }

    public function compileAnnotations(){
        if( $this->_bootstrap ) {
            return ;
        }
        $this->_bootstrap = true ;

        $base_parent_class  = sprintf('%s\\SymforceAbstractAnnotation', __NAMESPACE__) ;

        /**
         * @var $class_builder SymforceAnnotationBuilder
         */
        foreach($this->builders as $annotation_name => $class_builder ) {

            $class = new \Symforce\CoreBundle\PhpHelper\PhpClass( $this->getAnnotationClassName($annotation_name) ) ;

            $parent_name = $class_builder->getParentAnnotationName() ;
            if( $parent_name ) {
                $class->setParentClassName($this->getAnnotationClassName($parent_name) ) ;
            } else {
                $class->setParentClassName($base_parent_class) ;
            }

            $class->setConstant( SymforceAnnotationCache::ANNOTATION_NAME, $annotation_name) ;

            $group_name = $class_builder->getAnnotationGroupName() ;
            if( $group_name ) {
                $class->setConstant( SymforceAnnotationCache::ANNOTATION_GROUP_NAME, $group_name) ;
            }

            $value_property_name = $class_builder->getAnnotationValuePropertyName() ;
            if( $value_property_name ) {
                $class->setConstant( SymforceAnnotationCache::ANNOTATION_VALUE_NAME, $value_property_name) ;
            }

            if( $class_builder->getAnnotationValueAsKey() ) {
                $class->setConstant( SymforceAnnotationCache::ANNOTATION_VALUE_AS_KEY, 1 ) ;
            }

            if( $class_builder->getAnnotationValueNotNull() ) {
                $class->setConstant( SymforceAnnotationCache::ANNOTATION_VALUE_NOT_NULL, 1 ) ;
            }

            $doc    = sprintf(" * @Annotation") ;
            $annotation_target = $class_builder->getAnnotationTarget() ;
            if( $annotation_target ) {
                $doc    .= sprintf("\n * @Target({\"") . join('","', $annotation_target) . sprintf("\"})") ;
            }
            $class->setDocblock( $doc ) ;

            $value_property_name = null ;

            /**
             * @var $property_builder SymforceAnnotationPropertyBuilder
             */
            if( isset($this->properties_objects[ $annotation_name ]) ) {
                foreach($this->properties_objects[ $annotation_name ] as $property_name => $property_builder) {
                    $type = $property_builder->getType() ;
                    if( !$type ) $type = 'string' ;
                    $class->addProperty( $property_name, null, $type,  false, 'public'  );
                }
            }

            if( isset($this->public_properties[ $annotation_name ]) ) {
                foreach($this->public_properties[ $annotation_name ] as $property_name ) {
                    if( !$class->hasProperty($property_name) ) {
                        $property_builder = $this->public_properties_objects[ $property_name ] ;
                        $type = $property_builder->getType() ;
                        if( !$type ) $type = 'string' ;
                        $class->addProperty( $property_name, null, $type,  false, 'public' ) ;
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