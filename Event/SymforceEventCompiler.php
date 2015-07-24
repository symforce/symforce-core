<?php

namespace Symforce\CoreBundle\Event;

use Symforce\CoreBundle\Event\Builder\SymforceEventBuilder ;
use Symforce\CoreBundle\Event\Builder\SymforceEventArgsBuilder ;
use Symforce\CoreBundle\PhpHelper\PhpHelper ;

class SymforceEventCompiler {

    const TAG_NAME = 'sf.event.builder' ;
    const _TAG_NAME = 'sf.event.args_builder' ;

    private $_ignore_name_list    = array( 'event', 'builder', 'compiler', 'dispatcher', 'on', 'before', 'after', 'fire' ) ;
    private $_ignore_properties_list  = array( 'type', 'id', 'on', 'before', 'after', 'event', 'dispatcher' ) ;

    private $_tagName ;
    private $_events = array() ;
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

    /**
     * @param string $name
     * @return SymforceEventBuilder
     */
    private function getEventBuilderByName($name){
        return $this->_events[$name] ;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function hasEventBuilder($name) {
        return isset($this->_events[$name]) ;
    }

    public function addEventBuilder($id, array & $attributes){
        if( !isset($attributes['alias']) ) {
            throw new \Exception( sprintf("service(%s) with tags(name=%s) require alias", $id, self::TAG_NAME));
        }
        $name = $attributes['alias'] ;
        if( !PhpHelper::isClassName($name) || in_array($name, $this->_ignore_name_list) ) {
            throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) alias invalid", $id, self::TAG_NAME, $name) ) ;
        }
        if( $this->hasEventBuilder($name) ) {
            throw new \Exception(sprintf("service(%s) with tags(name=%s, alias=%s) conflict with service(%s)",
                $id, self::TAG_NAME, $name,  $this->getEventBuilderByName($name)->getId() ) ) ;
        }

        $builder = new SymforceEventBuilder() ;
        $builder->setId($id) ;
        $builder->setIndex( count($this->_events) ) ;
        $builder->setName($name) ;

        if( isset($attributes['parent']) ) {
            $builder->setParentName( $attributes['parent'] ) ;
        }

        $this->_events[$name] = $builder ;
    }

    public function addEventArgumentBuilder($id, array & $attributes) {
        if (!isset($attributes['alias']) ) {
            throw new \Exception( sprintf("service(%s, tags:{name:%s}) require alias", $id, self::_TAG_NAME ) ) ;
        }
        $name = $attributes['alias'] ;
        if(  !PhpHelper::isPropertyName($name) || in_array($name, $this->_ignore_properties_list) ){
            throw new \Exception(sprintf("service(%s, tags:{name:%s, alias:%s}) tag.alias invalid", $id, self::_TAG_NAME, $name) ) ;
        }
        if (!isset($attributes['parent'])) {
            throw new \Exception( sprintf("service(%s, tags:{name:%s, alias:%s}) require parent", $id, self::_TAG_NAME, $name) ) ;
        }
        $parent_name = $attributes['parent'] ;
        if ( !$this->hasEventBuilder($parent_name)) {
            throw new \Exception( sprintf("service(%s, tags:{name:%s, alias:%s, parent:%s}) tag.parent must be one of(%s)", $id, self::_TAG_NAME, $name, $parent_name ,
                join(',', array_keys($this->_events)) ) ) ;
        }
        $parent = $this->getEventBuilderByName($parent_name) ;

        if( $parent->hasEventArgumentBuilder( $name )  ) {
            throw new \Exception(sprintf("service(%s, tags:{name:%s, alias:%s, parent:%s}) conflict with service(%s)",
                $id, self::_TAG_NAME, $name, $parent_name,  $parent->getEventArgumentBuilderByName($name)->getId() ) ) ;
        }


        $builder = new SymforceEventArgsBuilder();
        $builder->setId($id) ;
        $builder->setName($name) ;

        if (!isset($attributes['type'])) {
            throw new \Exception( sprintf("service(%s) with tags(name=%s, alias=%s, parent=%s) require type", $id, self::_TAG_NAME, $name, $parent_name) ) ;
        }
        $builder->setType($attributes['type']) ;

        if ( isset($attributes['value'])) {
            $builder->setDefaultValue($attributes['value']);
        }

        $parent->addEventArgumentBuilder( $builder ) ;
    }

    private function hasParentsLoop(SymforceEventBuilder $builder, array & $parents) {
        $name   = $builder->getName() ;
        if( isset($parents[$name]) ) {
            $parents[$name] = $builder ;
            return true ;
        }
        $parents[$name] = $builder ;
        $parent_name = $builder->getParentName() ;
        if( $parent_name ) {
            return $this->hasParentsLoop( $this->getEventBuilderByName($parent_name),  $parents );
        }
        return false ;
    }

    public function compileEvents(){

        $base_parent_class  = sprintf('%s\\SymforceAbstractEvent', __NAMESPACE__) ;

        /**
         * @var $class_builder SymforceEventBuilder
         */
        foreach($this->_events as $event_name => $class_builder ) {

            $_parents = array() ;
            if( $this->hasParentsLoop($class_builder, $_parents) ) {
                throw new \Exception( sprintf("services(tag:{name:%s}) parent circular dependencies: %s ! ",  self::TAG_NAME, join(',', array_map(function(SymforceAnnotationBuilder $builder){
                    return sprintf("\n\t,service(%s, tag{alias:%s%s})", $builder->getId(), $this->getName(), $builder->getParentName() ? ', parent:' . $builder->getParentName() : '' );
                }, $_parents)) ) ) ;
            }

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
            foreach( $class_builder->getEventArgumentBuilders() as $property_name => $property_builder) {
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