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
    private $class_name ;

    /**
     * @var \ReflectionClass
     */
    public $reflection ;

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
    public function getClassName() {
        return $this->class_name ;
    }

    /**
     * @param string $name
     */
    public function setClassName($name) {
        $this->reflection = new \Reflection($name) ;
        $this->class_name = $name;
    }

}