<?php

namespace Symforce\CoreBundle\Event;


class SymforceEventCompiler {

    private $_tagName ;

    private $_events ;

    public function setTagName( $name ) {
        $this->_tagName = $name ;
    }

    public function getTagName() {
        return $this->_tagName  ;
    }

    public function addEventBuilder(SymforceEventBuilder $builder){
        $this->_events[ $builder->getName() ] = $builder ;
    }

    public function compileEvents(){

    }
}