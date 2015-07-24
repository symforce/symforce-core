<?php

namespace Symforce\CoreBundle\Annotation\Builder ;

final class SymforceAnnotationBuilder {

    /**
     * @var string
     */
    private $id ;

    /**
     * @var string
     */
    private $name ;

    /**
     * @var string
     */
    private $parent_name ;

    /**
     * @var string
     */
    private $group_name ;

    /**
     * @var string
     */
    private $target ;


    private $_value = null ;

    private $value_property_name ;

    private $value_as_key ;

    private $value_not_null ;

    private $public_properties ;
    private $properties = array() ;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id ;
    }

    /**
     * @param $name
     */
    public function setId($id)
    {
        $this->id = $id ;
    }

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
        $this->name = $name ;
    }

    /**
     * @return string
     */
    public function getParentName() {
        return $this->parent_name ;
    }

    /**
     * @param string $name
     */
    public function setParentName($name) {
        $this->parent_name = $name;
    }

    /**
     * @return string
     */
    public function getGroupName() {
        return $this->group_name ;
    }

    /**
     * @param string $name
     */
    public function setGroupName($name) {
        $this->group_name = $name ;
    }

    /**
     * @return array
     */
    public function getTarget() {
        return $this->target ;
    }

    public function setTarget($target)
    {
        if( is_string($target) ) {
            $this->target = preg_split('/\W+/', preg_replace('/^\W*|\W*$/', '', $target) ) ;
        } elseif( is_array($this->target) ){
            $this->target = $target;
        }
    }

    public function setPublicProperties( $public_properties ) {
        if( is_array($public_properties) ) {
            $this->public_properties = $public_properties ;
        } else {
            $this->public_properties = preg_split('/\s*,\s*/', trim($public_properties) );
        }
    }

    public function getPublicProperties( ){
        return $this->public_properties ;
    }

    public function getProperties( ){
        return $this->properties ;
    }

    public function setValue( $value ) {
        if( is_string($value) ) {
            $this->value_property_name = $value ;
        } else if( is_array($value) ) {
            if( isset($value['name']) ) {
                $this->value_property_name = $value['name'] ;
            }
            if( isset($value['as_key']) ) {
                $this->value_as_key = $value['as_key'] ? true: false  ;
            }
            if( isset($value['not_null']) ) {
                $this->value_not_null = $value['not_null'] ? true: false  ;
            }
        }
        $this->_value = $value ;
    }

    public function getValue() {
        return $this->_value ;
    }

    public function getValuePropertyName() {
        return $this->value_property_name ;
    }

    public function getValueAsKey(){
        return $this->value_as_key ;
    }

    public function getValueNotNull(){
        return $this->value_not_null ;
    }

    public function hasPropertyBuilder($name) {
        return isset($this->properties[$name]) ;
    }

    /**
     * @param $name
     * @return SymforceAnnotationPropertyBuilder
     */
    public function getPropertyBuilderByName($name) {
        return $this->properties[$name] ;
    }

    public function addPropertyBuilder(SymforceAnnotationPropertyBuilder $builder) {
        $this->properties[ $builder->getName() ] = $builder ;
    }

}