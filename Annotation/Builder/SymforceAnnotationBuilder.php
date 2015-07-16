<?php

namespace Symforce\CoreBundle\Annotation\Builder ;

final class SymforceAnnotationBuilder {

    /**
     * @var string
     */
    private $name ;

    /**
     * @var string
     */
    private $parent_annotation_name ;

    /**
     * @var string
     */
    private $annotation_group_name ;

    /**
     * @var string
     */
    private $annotation_target ;

    private $annotation_value_property_name ;

    private $annotation_value_as_key ;

    private $annotation_value_not_null ;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        if( null === $this->name ) {
            $this->name = $name ;
        }
    }

    /**
     * @return string
     */
    public function getParentAnnotationName() {
        return $this->parent_annotation_name ;
    }

    /**
     * @param string $name
     */
    public function setParentAnnotationName($name) {
        if( null === $this->parent_annotation_name ) {
            $this->parent_annotation_name = $name;
        }
    }

    /**
     * @return string
     */
    public function getAnnotationGroupName() {
        return $this->annotation_group_name ;
    }

    /**
     * @param string $name
     */
    public function setAnnotationGroupName($name) {
        if( null === $this->annotation_group_name ) {
            $this->annotation_group_name = $name ;
        }
    }

    /**
     * @return array
     */
    public function getAnnotationTarget() {
        if( is_string($this->annotation_target) ) {
            $this->annotation_target = preg_split('/\W+/', preg_replace('/^\W*|\W*$/', '', $this->annotation_target) ) ;
        } elseif( !is_array($this->annotation_target) ){
            $this->annotation_target = null ;
        }
        return $this->annotation_target ;
    }

    public function setAnnotationTarget($target)
    {
        if (null === $this->annotation_target) {
            $this->annotation_target = $target;
        }
    }

    public function setAnnotationValuePropertyName($name) {
        if( null === $this->annotation_value_property_name ) {
            $this->annotation_value_property_name = $name ;
        }
    }

    public function getAnnotationValuePropertyName(){
        return $this->annotation_value_property_name ;
    }

    public function setAnnotationValueAsKey($value) {
        if( null === $this->annotation_value_as_key ) {
            $this->annotation_value_as_key = $value ;
        }
    }

    public function getAnnotationValueAsKey(){
        return $this->annotation_value_as_key ;
    }

    public function setAnnotationValueNotNull($value) {
        if( null === $this->annotation_value_not_null ) {
            $this->annotation_value_not_null = $value ;
        }
    }

    public function getAnnotationValueNotNull(){
        return $this->annotation_value_not_null ;
    }
}