<?php

namespace Symforce\CoreBundle\Annotation\Builder ;

final class SymforceAnnotationClassBuilder {

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
    private $camelize_name ;

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

    public function getCamelizeName(){
        return $this->camelize_name ;
    }

    public function setCamelizeName($name){
        $this->camelize_name = $name ;
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
        if( !is_array($public_properties) ) {
            $public_properties = preg_split('/\s*,\s*/', trim($public_properties) );
        }
        $properties = array() ;
        $this->public_properties = array() ;
        foreach($public_properties as $_property) {
            $pos = strpos($_property, ':') ;
            if( false === $pos ) {
                $this->public_properties[] = $_property ;
            } else {
                $_property_name = substr($_property, 0, $pos) ;
                $_property_type = substr($_property, $pos + 1 ) ;
                $properties[ $_property_name ] = $_property_type ;
            }
        }
        return $properties ;
    }

    public function getPublicProperties( ){
        return $this->public_properties ;
    }

    public function getProperties( ){
        return $this->properties ;
    }

    public function getValuePropertyName() {
        return $this->value_property_name ;
    }

    public function setValuePropertyName( $name ) {
        $this->value_property_name = $name ;
    }

    public function getValueAsKey(){
        return $this->value_as_key ;
    }

    public function setValueAsKey( $value ){
        $this->value_as_key = $value ? true : false ;
    }

    public function getValueNotNull(){
        return $this->value_not_null ;
    }

    public function setValueNotNull($value){
        $this->value_not_null = $value ? true : false ;
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