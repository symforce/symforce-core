<?php

namespace Symforce\CoreBundle\Annotation;

use Doctrine\Common\Annotations\Reader;
use Symforce\CoreBundle\PhpHelper\PhpHelper ;

class SymforceAnnotationCache implements \Serializable {

    const ANNOTATION_NAME = 'SYMFORCE_ANNOTATION_NAME' ;
    const ANNOTATION_GROUP_NAME = 'SYMFORCE_ANNOTATION_GROUP_NAME' ;
    const ANNOTATION_VALUE_NAME = 'SYMFORCE_ANNOTATION_VALUE_PROPERTY' ;
    const ANNOTATION_VALUE_AS_KEY = 'SYMFORCE_ANNOTATION_VALUE_AS_KEY' ;
    const ANNOTATION_VALUE_NOT_NULL = 'SYMFORCE_ANNOTATION_VALUE_NOT_NULL' ;


    public  $class_name ;
    public  $class_annotations = array() ;
    public  $properties_annotations = array() ;
    public  $other_annotations = array() ;

    public function __construct(Reader $reader, \ReflectionClass $reflect, array & $cached_namespace ) {
        $class_name = $reflect->getName() ;
        $this->class_name   = $class_name ;

        $this->addAnnotation($reflect, $cached_namespace, $reader->getClassAnnotations($reflect) ) ;

        foreach ($reflect->getProperties() as $p ) {
            $property_name  = $p->getName() ;

            if( PhpHelper::isSQLKeywords($property_name) ) {
                throw new \Exception(sprintf("`%s->%s` can not use SQL key words as property name", $class_name, $property_name));
            }

            $this->properties_annotations[ $property_name ] = array() ;

            self::addAnnotation($reflect, $cached_namespace, $reader->getPropertyAnnotations($p), $property_name ) ;

            if( count($this->properties_annotations[ $property_name ]) < 1 ) {
                unset( $this->properties_annotations[ $property_name ] ) ;
            }
        }

    }

    private function addAnnotation(\ReflectionClass $reflect, array & $cached_namespace, array $annotations , $property_name = null ) {
        foreach($annotations  as $annotation ) {

            if( !($annotation instanceof SymforceAbstractAnnotation) ) {
                $class_name = get_class($annotation) ;
                $_pos   = strrpos($class_name, '\\');
                $namespace  = substr($class_name, 0, $_pos) ;
                if( in_array($namespace, $cached_namespace) ) {
                    if( !$property_name ) {
                        $this->other_annotations['class'][$class_name] = $annotation ;
                    } else {
                        $this->other_annotations['properties'][$property_name][$class_name] = $annotation ;
                    }
                }
                continue ;
            }

            /**
             * @var $annotation SymforceAbstractAnnotation
             */

            $annotation_name = $annotation::SYMFORCE_ANNOTATION_NAME ;

            if( !$property_name ) {
                if( !isset($this->class_annotations[$annotation_name]) ) {
                    $this->class_annotations[$annotation_name] = SymforceAnnotationCacheValue::Init($reflect, $property_name, $annotation) ;
                } else {
                    $this->class_annotations[$annotation_name]->addAnnotation( $annotation ) ;
                }
            } else {
                if( !isset($this->properties_annotations[$property_name][$annotation_name]) ) {
                    $this->properties_annotations[$property_name][$annotation_name] = SymforceAnnotationCacheValue::Init($reflect, $property_name, $annotation) ;
                } else {
                    $this->properties_annotations[$property_name][$annotation_name]->addAnnotation( $annotation ) ;
                }
            }

        }
    }

    public function serialize() {
        $data = array( $this->class_name, $this->other_annotations, array(), array() ) ;

        /**
         * @var $annotation_value SymforceAnnotationCacheValue
         */
        foreach($this->class_annotations as $annotation_name => $annotation_value ) {
            $data[2][ $annotation_name ] = array( $annotation_value->value, $annotation_value->values ) ;
        }
        foreach($this->properties_annotations as $property_name => $property_annotations) {
            foreach($property_annotations as $annotation_name => $annotation_value ) {
                $data[3][$property_name][ $annotation_name ] = array( $annotation_value->value, $annotation_value->values ) ;
            }
        }
        return serialize( $data ) ;
    }

    public function unserialize($data) {
        $_data = unserialize($data) ;
        $this->class_name = $_data[0] ;
        $this->other_annotations = $_data[1] ;

        $reflect = new \ReflectionClass($this->class_name) ;

        foreach($_data[2] as $annotation_name => $annotations ) {
            $this->class_annotations[$annotation_name] =  SymforceAnnotationCacheValue::Unserialize($reflect, null, $annotation_name, $annotations) ;
        }

        foreach($_data[3] as $property_name => $property_annotations ) {
            foreach($property_annotations as $annotation_name => $annotations ) {
                $this->properties_annotations[$property_name][$annotation_name] =  SymforceAnnotationCacheValue::Unserialize($reflect, $property_name, $annotation_name, $annotations) ;
            }
        }
    }
}