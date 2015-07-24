<?php

namespace Symforce\CoreBundle\Annotation\Builder ;

final class SymforceAnnotationPropertyBuilder {

    /**
     * @var string
     */
    private $id ;

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

}