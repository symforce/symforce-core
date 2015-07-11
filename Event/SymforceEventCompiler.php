<?php

namespace Symforce\CoreBundle\Event;

use Symforce\CoreBundle\Event\Builder\SymforceEventBuilder ;
use Symforce\CoreBundle\Event\Builder\SymforceEventArgsBuilder ;

class SymforceEventCompiler {


    private $_tagName ;
    private $_bootstrap = false ;

    private $_events = array() ;
    private $_events_args = array() ;
    private $_classNameCache = array() ;

    public function setTagName( $name ) {
        if( $this->_bootstrap ) {
            return ;
        }
        $this->_tagName = $name ;
    }

    public function getTagName() {
        return $this->_tagName  ;
    }

    public function addEventBuilder(SymforceEventBuilder $builder){
        if( $this->_bootstrap ) {
            return ;
        }
        $this->_events[ $builder->getName() ] = $builder ;
    }

    public function addEventArgumentBuilder(SymforceEventArgsBuilder $builder){
        if( $this->_bootstrap ) {
            return ;
        }
        $this->_events_args[ $builder->getEventName() ] [ $builder->getName() ] = $builder ;
    }

    public function compileEvents(){
        if( $this->_bootstrap ) {
            return ;
        }
        $this->_bootstrap = true ;

        $base_parent_class  = sprintf('%s\\SymforceAbstractEvent', __NAMESPACE__) ;

        /**
         * @var $class_builder SymforceEventBuilder
         */
        foreach($this->_events as $event_name => $class_builder ) {

            $class = new \Symforce\CoreBundle\PhpHelper\PhpClass( $this->getClassName($event_name) ) ;

            $parent_name = $class_builder->getParentName() ;
            if( $parent_name ) {
                $class->setParentClassName($this->getClassName($parent_name) ) ;
            } else {
                $class->setParentClassName($base_parent_class) ;
            }

            /**
             * @var $property_builder SymforceEventArgsBuilder
             */
            if( isset($this->_events_args[ $event_name ]) ) foreach($this->_events_args[ $event_name ] as $property_name => $property_builder) {
                $type = $property_builder->getType() ;
                if( !$type ) $type = 'string' ;
                $class->addProperty( $property_name, null, $type,  false, 'public' );
            }

            $class->writeCache() ;
        }
    }

    protected function getClassName($name) {
        if( !isset($this->_classNameCache[$name]) ) {
            $this->_classNameCache[$name] = sprintf('Symforce\\Event\\%s', \Symforce\CoreBundle\PhpHelper\PhpHelper::camelize($name) ) ;
        }
        return $this->_classNameCache[$name] ;
    }
}