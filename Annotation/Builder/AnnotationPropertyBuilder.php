<?php

namespace Symforce\CoreBundle\Annotation\Builder ;

final class AnnotationPropertyBuilder {

    /**
     * @var string
     */
    private $annotation_name ;

    /**
     * @var string
     */
    private $name ;

    /**
     * @var string
     */
    private $type ;

    /**
     * @var string
     */
    private $is_value_property ;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName( $name )
    {
        if( null === $this->name ) {
            $this->name = $name ;
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type ;
    }

    /**
     * @param string $name
     */
    public function seType( $type )
    {
        if( null === $this->type ) {
            $this->type = $type ;
        }
    }

    /**
     * @return string
     */
    public function getAnnotationName(){
        return $this->annotation_name ;
    }

    /**
     * @param string $name
     */
    public function setAnnotationName( $name ){
         if( null ===  $this->annotation_name ) {
             $this->annotation_name = $name ;
         }
    }

    public function setIsValueProperty( $value ){
        if( null === $this->is_value_property ) {
            $this->is_value_property = $value ;
        }
    }

    public function getIsValueProperty(){
        return $this->is_value_property ;
    }
}