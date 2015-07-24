<?php

namespace Symforce\CoreBundle\PhpHelper;

class PhpProperty extends \CG\Generator\PhpProperty {
    
    /**
     * @var PhpClass 
     */
    protected   $_class = null ;
    
    /**
     * @var string 
     */
    private     $_name = null ;
    
    /**
     * @var bool 
     */
    protected   $_get = null ;
    
    /**
     * @var string 
     */
    protected   $_type = null ;
    
    /**
     * @var bool 
     */
    protected   $_lazy = null ;

    public function __construct($name = null) {
        $this->setName($name);
    }
    
    /**
     * @param \Symforce\CoreBundle\PhpHelper\PhpClass $class
     * @return \Symforce\CoreBundle\PhpHelper\PhpProperty
     */
    public function setClass(PhpClass $class){
        $this->_class   = $class ;
        $class->setProperty( $this ) ;
        return $this ;
    }
    
    /**
     * @param bool $_get
     * @return \Symforce\CoreBundle\PhpHelper\PhpProperty
     */
    public function useGetter( $_get ){
        $this->_get = !! $_get ;
        return $this ;
    }
    
    /**
     * @param bool $_lazy
     * @return \Symforce\CoreBundle\PhpHelper\PhpProperty
     */
    public function setLazy( $_lazy = true ){
        $this->_lazy = !! $_lazy ;
        return $this ;
    }
    
    /**
     * @param string $type
     * @return \Symforce\CoreBundle\PhpHelper\PhpProperty
     */
    public function setType( $type ){
        $this->type = $type ;
        return $this;
    }
    
    public function getFixedName() {
        if( null === $this->_name ) {
            $this->_name = preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
                        return ('.' === $match[1] ? '_' : '') . strtoupper($match[2]);
                    }, $this->getName() ) ; 
        }
        return $this->_name ;
    }
    
    public function genGetter(){
        if( $this->_get  ) {
            
            $get_name = 'get' . ucfirst( $this->getFixedName() ) ;
            
            $method = $this->_class->setMethod(
                        \CG\Generator\PhpMethod::create( $get_name )
                        ->setFinal(true)
                        ->setBody('return $this->' . $this->getName() . ';')
            ) ;
            if( $this->_type ) {
                $method->setDocblock('/** @return ' .  $this->_type  . ' */') ;
            }
        }
        return $this ;
    }
    
    public function writeCache(\Symforce\CoreBundle\PhpHelper\PhpWriter $writer ) {
        
        $default_value  = $this->getDefaultValue() ;
        if( $this->_lazy ) {
            $this->_class->getLazyWriter()->writeln( '$this->' . $this->getName() . ' = ' . PhpHelper::compilePropertyValue( $default_value )  . ' ; ' ) ;
            $default_value  = null ;
        }

        $writer->write("\n") ;
        if( $this->getDocblock() ) {
            $writer->writeln( $this->getDocblock() ) ;
        }
        $writer
                ->write( $this->getVisibility() ) 
                ->write( ' $' ) 
                ->write( $this->getName() ) ;

        if( null !== $default_value ) {
            $writer
                ->write( ' = ' )
                ->write(  PhpHelper::compilePropertyValue( $default_value ) ) ;
        }

        $writer->writeln(";") ;

        if( $this->_get ) {
            $this->genGetter() ;
        }
    }
}