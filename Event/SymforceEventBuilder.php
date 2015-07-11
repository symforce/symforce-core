<?php

namespace Symforce\CoreBundle\Event;


class SymforceEventBuilder {

    /**
     * @var integer
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
    public function setName($name)
    {
        if( null === $this->name ) {
            $this->name = $name ;
        }
    }

    /**
     * @return string
     */
    public function getParentName()
    {
        return $this->parent_name ;
    }

    /**
     * @param string $name
     */
    public function setParentName($name)
    {
        if( null === $this->parent_name ) {
            $this->parent_name = $name ;
        }
    }
}