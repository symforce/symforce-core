<?php

namespace Symforce\CoreBundle\Event;

use Symforce\CoreBundle\Event\Builder\SymforceEventBuilder ;
use Symforce\CoreBundle\Event\Builder\SymforceEventArgsBuilder ;

class SymforceEventCompiler {

    private $_tagName ;

    private $_events = array() ;
    private $_events_args = array() ;
    private $_classNameCache = array() ;

    public function setTagName( $name ) {
        $this->_tagName = $name ;
    }

    public function getTagName() {
        return $this->_tagName  ;
    }

    public function addEventBuilder(SymforceEventBuilder $builder){
        $this->_events[ $builder->getName() ] = $builder ;
    }

    public function addEventArgumentBuilder(SymforceEventArgsBuilder $builder){
        $this->_events_args[ $builder->getEventName() ] [ $builder->getName() ] = $builder ;
    }

    public function compileEvents(){

    }

    protected function getClassName($name) {
        if( !isset($this->_classNameCache[$name]) ) {
            $this->_classNameCache[$name] = sprintf('Symforce\\Event\\%s', \Symforce\CoreBundle\PhpHelper\PhpHelper::camelize($name) ) ;
        }
        return $this->_classNameCache[$name] ;
    }
}