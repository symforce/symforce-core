<?php

namespace Symforce\CoreBundle\Event\Builder ;


final class SymforceEventBuilder {

    /**
     * @var integer
     */
    private $id ;

    /**
     * @var integer
     */
    private $index ;

    /**
     * @var string
     */
    private $name ;


    /**
     * @var string
     */
    private $parent_name ;

    private $args_builders = array() ;

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
        $this->id = $id ;
    }
    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index ;
    }

    /**
     * @param integer $id
     */
    public function setIndex($index)
    {
        $this->index = $index ;
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

    /**
     * @param string $name
     * @return SymforceEventArgsBuilder
     */

    public function getEventArgumentBuilderByName($name) {
        return $this->args_builders[$name] ;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasEventArgumentBuilder($name) {
        return isset( $this->args_builders[$name]) ;
    }

    public function addEventArgumentBuilder(SymforceEventArgsBuilder $builder){
        $this->args_builders[ $builder->getName() ] = $builder ;
    }

    public function getEventArgumentBuilders(){
        return $this->args_builders ;
    }
}