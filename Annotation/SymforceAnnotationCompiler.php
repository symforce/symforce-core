<?php

namespace Symforce\CoreBundle\Annotation ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationBuilder  ;
use Symforce\CoreBundle\Annotation\Builder\SymforceAnnotationPropertyBuilder  ;

class SymforceAnnotationCompiler {

    const ANNOTATION_GROUP_NAME = 'SYMFORCE_ANNOTATION_GROUP' ;
    const ANNOTATION_VALUE_NAME = 'SYMFORCE_ANNOTATION_VALUE_PROPERTY' ;

    private $_bootstrap = false ;

    private $_classNameCache = array() ;

    private $builders = array() ;

    private $properties = array() ;

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
        $this->properties[ $name ][ $property_name ] = $builder ;
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

            $group_name = $class_builder->getAnnotationGroupName() ;
            if( $group_name ) {
                $class->setConstant( self::ANNOTATION_GROUP_NAME, $group_name) ;
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
            if( isset($this->properties[ $annotation_name ]) ) foreach($this->properties[ $annotation_name ] as $property_name => $property_builder) {
                $type = $property_builder->getType() ;
                if( !$type ) $type = 'string' ;
                $class->addProperty( $property_name, null, $type,  false, 'public'  );
                if( $property_builder->getIsValueProperty() ) {
                    $class->setConstant( self::ANNOTATION_VALUE_NAME, $property_name ) ;
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