<?php

namespace Symforce\CoreBundle\Event\Builder;

final class SymforceEventArgsBuilder {

    /**
     * @var integer
     */
    private $id ;

    /**
     * @var string
     */
    private $event_name ;

    /**
     * @var string
     */
    private $name ;

    /**
     * @var string
     */
    private $type ;

    /**
     * @var mixed
     */
    private $default_value ;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id ;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        if( null === $this->id ) {
            $this->id = $id ;
        }
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
    public function getEventName(){
        return $this->event_name  ;
    }

    /**
     * @param string $name
     */
    public function setEventName( $name ){
        if( null ===  $this->event_name  ) {
            $this->event_name  = $name ;
        }
    }

    public function getDefaultValue(){
        return $this->default_value ;
    }

    public function setDefaultValue($value){
        if( null ===  $this->default_value  ) {
            $this->default_value = $value;
        }
    }

}