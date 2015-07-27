<?php

namespace Symforce\CoreBundle\Annotation\Builder;

class SymforceAnnotationTypeBuilder {

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

}