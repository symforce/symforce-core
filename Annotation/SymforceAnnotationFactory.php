<?php

namespace Symforce\CoreBundle\Annotation ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

use Symforce\CoreBundle\Annotation\Compiler\AnnotationClassCompiler ;
use Symforce\CoreBundle\Annotation\Compiler\AnnotationPropertyCompiler ;

class SymforceAnnotationFactory {

    const ANNOTATION_GROUP_NAME = 'SYMFORCE_ANNOTATION_GROUP' ;
    const ANNOTATION_VALUE_NAME = 'SYMFORCE_ANNOTATION_VALUE_PROPERTY' ;

    private $_bootstrap = false ;

    private $_classNameCache = array() ;

    private $compilers = array() ;

    private $properties = array() ;

    /**
     * @var ContainerInterface
     */
    protected $_container;

    /**
     * @param ContainerInterface $container A ContainerInterface instance
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->_container = $container ;
    }

    public function addAnnotationClassCompiler(AnnotationClassCompiler $compiler) {
        if( $this->_bootstrap ) {
            return ;
        }
        $name = $compiler->getName() ;
        $this->compilers[ $name ] = $compiler ;
    }

    public function addAnnotationPropertyCompiler(AnnotationPropertyCompiler $compiler) {
        if( $this->_bootstrap ) {
            return ;
        }
        $name = $compiler->getAnnotationName() ;
        if( !isset($this->compilers[ $name ]) ) {
            throw new \Exception(sprintf("annotation(%s) not exists", $name )) ;
        }

        $property_name = $compiler->getName() ;
        if( empty($property_name) || !ctype_alpha($property_name) ){
            $rc = new \ReflectionClass($compiler) ;
            throw new \Exception(sprintf("annotation(%s) name is invalid in class(%s) file(%s)", $property_name, $rc->getName(),  $rc->getFileName() )) ;
        }

        $this->properties[ $name ][ $property_name ] = $compiler ;
    }

    public function buildAnnotations(){
        if( $this->_bootstrap ) {
            return ;
        }
        $this->_bootstrap = true ;

        $base_parent_class  = sprintf('%s\\SymforceAbstractAnnotation', __NAMESPACE__) ;

        /**
         * @var $class_compiler AnnotationClassCompiler
         */
        foreach($this->compilers as $annotation_name => $class_compiler ) {

            $class = new \Symforce\CoreBundle\PhpHelper\PhpClass( $this->getAnnotationClassName($annotation_name) ) ;

            $parent_name = $class_compiler->getParentAnnotationName() ;
            if( $parent_name ) {
                if( !isset($parent_name) ) {
                    $rc = new \ReflectionClass($class_compiler) ;
                    throw new \Exception(sprintf("annotation(%s)->parent_annotation_name(%s) not exists in class(%s) file(%s)", $rc->getName(), $parent_name, $rc->getFileName() )) ;
                }
                $class->setParentClassName($this->getAnnotationClassName($parent_name) ) ;
            } else {
                $class->setParentClassName($base_parent_class) ;
            }

            $group_name = $class_compiler->getAnnotationGroupName() ;
            if( $group_name ) {
                $class->setConstant( self::ANNOTATION_GROUP_NAME, $group_name) ;
            }

            $doc    = sprintf(" * @Annotation") ;
            $annotation_target = $class_compiler->getAnnotationTarget() ;
            if( $annotation_target ) {
                $doc    .= sprintf("\n * @Target({\"") . join('","', $annotation_target) . sprintf("\"})") ;
            }
            $class->setDocblock( $doc ) ;

            $value_property_name = null ;
            /**
             * @var $property_compiler AnnotationPropertyCompiler
             */
            if( isset($this->properties[ $annotation_name ]) ) foreach($this->properties[ $annotation_name ] as $property_name => $property_compiler) {
                $type = $property_compiler->getType() ;
                if( !$type ) $type = 'string' ;
                $class->addProperty( $property_name, null, $type,  false, 'public'  );
                if( $property_compiler->getIsValueProperty() ) {
                    $class->setConstant( self::ANNOTATION_VALUE_NAME, $property_name ) ;
                }
            }

            $class->writeCache() ;
        }
    }

    protected function getAnnotationClassName($name) {
        if( !isset($this->_classNameCache[$name]) ) {
            $this->_classNameCache[$name] = sprintf('Symforce\\Annotation\\%s', ucfirst($name) ) ;
        }
        return $this->_classNameCache[$name] ;
    }

}