<?php

namespace Symforce\CoreBundle\Annotation;

class SymforceAnnotationCacheValue {

    /**
     * @var \ReflectionClass
     */
    public $class_reflection ;
    public $property_name ;
    public $annotation_name ;

    /**
     * @var SymforceAbstractAnnotation
     */
    public $value ;

    /**
     * @var array
     */
    public $values = array() ;

    private function _init(\ReflectionClass $rc, $property_name, SymforceAbstractAnnotation $annotation) {
        $this->class_reflection = $rc ;
        $this->property_name = $property_name ;
        $this->annotation_name = $annotation::SYMFORCE_ANNOTATION_NAME ;

        $value_property_name = $annotation::SYMFORCE_ANNOTATION_VALUE_PROPERTY ;

        if( $value_property_name ) {
            $value_property_as_key = $annotation::SYMFORCE_ANNOTATION_VALUE_AS_KEY ;
            $value_property_not_null = $annotation::SYMFORCE_ANNOTATION_VALUE_NOT_NULL ;
            $value_property_value = $annotation->$value_property_name ;

            if( !$value_property_value ) {
                if( $value_property_not_null ) {
                    throw new \Exception( sprintf("%s annotation.%s can not be null", $this->getAnnotationResourceDescription(), $value_property_name ) ) ;
                }
                $this->value    = $annotation ;
            } else {
                if( $value_property_as_key ) {
                        $this->values[ $value_property_value ] = $annotation ;
                } else {
                    $this->value    = $annotation ;
                }
            }
        } else {
            $this->value    = $annotation ;
        }
    }

    static public function Unserialize(\ReflectionClass $rc, $property_name, $name, array & $values){
        $object = new SymforceAnnotationCacheValue ;
        $object->class_reflection = $rc ;
        $object->property_name = $property_name ;
        $object->annotation_name = $name ;
        $object->value = $values[0] ;
        $object->values = $values[1] ;
        return $object ;
    }

    /**
     * @param \ReflectionClass $rc
     * @param $property_name
     * @param SymforceAbstractAnnotation $annotation
     * @return SymforceAnnotationCacheValue
     */
    static public function Init(\ReflectionClass $rc, $property_name, SymforceAbstractAnnotation $annotation) {
        $object = new SymforceAnnotationCacheValue ;
        $object->_init($rc, $property_name, $annotation) ;
        return $object ;
    }



    public function addAnnotation(SymforceAbstractAnnotation $annotation){

        $value_property_name = $annotation::SYMFORCE_ANNOTATION_VALUE_PROPERTY ;

        if( $value_property_name ) {
            $value_property_as_key = $annotation::SYMFORCE_ANNOTATION_VALUE_AS_KEY ;
            $value_property_not_null = $annotation::SYMFORCE_ANNOTATION_VALUE_NOT_NULL ;
            $value_property_value = $annotation->$value_property_name ;

            if( !$value_property_value ) {
                if( $value_property_not_null ) {
                    throw new \Exception( sprintf("%s annotation.%s can not be null", $this->getAnnotationResourceDescription(), $value_property_name ) ) ;
                }
                if( $this->value ) {
                    throw new \Exception( sprintf("%s annotation.%s(null) duplicate", $this->getAnnotationResourceDescription(), $value_property_name ) ) ;
                }
                $this->value    = $annotation ;
            } else {
                if( $value_property_as_key ) {
                    if( isset($this->values[ $value_property_value ]) ) {
                        throw new \Exception( sprintf("%s annotation.%s(%s) duplicate", $this->getAnnotationResourceDescription(), $value_property_name, $value_property_value ) ) ;
                    }
                    $this->values[ $value_property_value ] = $annotation ;
                } else {
                    if( $this->value ) {
                        throw new \Exception( sprintf("%s annotation.%s(%s) duplicate", $this->getAnnotationResourceDescription(), $value_property_name, $value_property_value ) ) ;
                    }
                    $this->value    = $annotation ;
                }
            }
        } else {
            if( $this->value ) {
                throw new \Exception( sprintf("%s duplicate", $this->getAnnotationResourceDescription() ) ) ;
            }
            $this->value    = $annotation ;
        }
    }

    public function getAnnotationResourceDescription(){
        $class_name = $this->class_reflection->getName() ;
        if( $this->property_name  ) {
            return sprintf('class(%s) property(%s) @annotation(%s)', $class_name, $this->property_name, $this->annotation_name );
        }
        return sprintf('class(%s) @annotation(%s)', $class_name, $this->property_name, $this->annotation_name );
    }

}