<?php

namespace Symforce\CoreBundle\Annotation;

use Doctrine\Common\Annotations\Reader;
use Symforce\CoreBundle\PhpHelper\PhpHelper ;

class SymforceAnnotationCache implements \Serializable {

    /**
     * @var string
     */
    public  $name ;
    /**
     * @var \ReflectionClass
     */
    public  $reflection ;

    protected $class_annotations = array() ;
    protected $properties_annotations = array() ;
    protected $other_annotations = array() ;

    public function __construct(Reader $reader, \ReflectionClass $reflect, array & $cached_namespace ) {

        $class_name = $reflect->getName() ;
        $this->name   = $class_name ;
        $this->reflection = $reflect ;

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

    public function getName(){
        return $this->name ;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasClassValue($name ){
        return isset($this->class_annotations[$name]) ;
    }

    /**
     * @param string $name
     * @return \Symforce\CoreBundle\Annotation\SymforceAbstractAnnotation|array
     * @throws \Exception
     */
    public function getClassValue($name, $fetch_values = false ){
        if( !isset($this->class_annotations[$name]) ) {
            throw new \Exception ;
        }
        if( $fetch_values ) {
            return $this->class_annotations[$name]->values ;
        }
        return $this->class_annotations[$name]->value ;
    }


    /**
     * @param string $property_name
     * @param string $name
     * @return bool
     */
    public function hasPropertyValue($property_name, $name){
        return isset($this->properties_annotations[$property_name][$name]) ;
    }


    /**
     * @param $property_name
     * @param $name
     * @return \Symforce\CoreBundle\Annotation\SymforceAbstractAnnotation|array
     * @throws \Exception
     */
    public function getPropertyValue($property_name, $name, $fetch_values = false){
        if( !$this->reflection->hasProperty($property_name) ) {
            throw new  \Exception( sprintf("class property(%s->%s) not exists in file: %s ", $this->name, $property_name, $this->reflection->getFileName() ));
        }
        if( !isset($this->properties_annotations[$property_name][$name]) ) {
            throw new \Exception ;
        }
        if( $fetch_values ) {
            $this->properties_annotations[$property_name][$name]->values ;
        }
        return $this->properties_annotations[$property_name][$name]->value ;
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
        $this->class_reflection = new \ReflectionClass( $this->class_name ) ;
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