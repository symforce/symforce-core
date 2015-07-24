<?php

namespace Symforce\CoreBundle\PhpHelper;

class PhpClass extends \CG\Generator\PhpClass {

    /**
     * @var \Symforce\CoreBundle\PhpHelper\PhpWriter
     */
    protected $lazy_writer ;
    
    protected $lazy_properties  = array() ;
    protected $_traits = array() ;

    public function addTrait($_trait) {
        if( !in_array($_trait, $this->_traits) ) {
            $this->_traits[] = $_trait ;
        }
    }
    
    /**
     * @param string $name
     * @return \Symforce\CoreBundle\PhpHelper\PhpMethod
     */
    public function addMethod( $name , $visible = false) {
        $method  = new PhpMethod($name) ;
        $method
                ->setFinal(true)
                ->setVisibility( $visible ? 'public' : 'protected' )
                ;
        $this->setMethod( $method ) ;
        return $method ;
    }

    /**
     * @param string $name
     * @return \Symforce\CoreBundle\PhpHelper\PhpMethod
     */
    public function getMethod($name) {
        if( !$this->hasMethod($name) ) {
            throw new \Exception ;
        }
        $methods = $this->getMethods() ;
        return $methods[$name] ;
    }

    /**
     * @param string $name
     * @param string $key
     * @param mixed $value
     * @return \Symforce\CoreBundle\PhpHelper\PhpClass
     */
    public function addLazyArray($name, $key, $value = null, $visible = false ) {
        $_visible = $visible ? 'public' : 'protected' ;
        if( !isset($this->lazy_properties[$_visible][$name]) ) {
            $this->lazy_properties[$_visible][$name]   = array() ;
        }
        if( isset($this->lazy_properties[$_visible][$name][ $key ]) ) {
            if( is_array($this->lazy_properties[$_visible][$name][ $key ]) && is_array($value) ) {
                $this->lazy_properties[$_visible][$name][ $key ]  = array_merge( $this->lazy_properties[$_visible][$name][ $key ] , $value  ) ;
            } else {
                throw new \Exception( sprintf( 'overwride lazy property for %s->%s[%s] ', $this->getName(), $name, $key ) );
            }
        } else {
            $this->lazy_properties[$_visible][$name][ $key ]   = $value ;
        }
        return $this ;
    }

    /**
     * @return \Symforce\CoreBundle\PhpHelper\PhpWriter
     */
    public function getLazyWriter() {
        if( null === $this->lazy_writer ) {
            $method = $this->addMethod('__wakeup') ; 
            $this->lazy_writer  = $method->getWriter() ;
            
        }
        return $this->lazy_writer  ;
    }
    
    /**
     * @param string $name
     * @param mixed $value
     * @param string $type
     * @param bool $_get
     * @param string $visibility
     * @param bool $_lazy
     * @return \Symforce\CoreBundle\PhpHelper\PhpProperty
     */
    public function addProperty($name, $value, $type = null , $_get = false, $visible = false, $_lazy = false ) {
        $property   = new PhpProperty($name) ;
        if( null === $type ) {
            $type   = is_object( $value ) ? get_class( $value ) : gettype( $value ) ;
        }
        $property
                ->setClass( $this )
                ->setDocblock('/** @var ' . $type . ' */')
                ->setVisibility( $visible ? 'public' : 'protected' )
                ->setDefaultValue($value)
                ->useGetter( $_get )
                ->setLazy( $_lazy )
                ;
        return $this ;
    }


    public function writeCache( $_class_file = null ) {

        if( !$_class_file ) {
            $_class_file    = \Symforce\CoreBundle\PhpHelper\PhpHelper::findFileByClassName($this->getName()) ;
        }

        $shortName = pathinfo($_class_file, \PATHINFO_FILENAME );
        $namespace = $this->getNamespace() ;

        $writer = new \Symforce\CoreBundle\PhpHelper\PhpWriter();
        $writer
            ->writeln("<?php\n")
        ;

        if ( !empty($namespace) ) {
            $writer->writeln("namespace " . $namespace . ";\n") ;
        }
        
        $imports    = $this->getUseStatements() ; 
        
        foreach($imports as $alias => $use ) {
            $_alias = substr( $use, -1 - strlen($alias) );
            if( $_alias == '\\' . $alias ) {
                $writer->writeln(sprintf("use %s ;", $use));
            } else {
                $writer->writeln(sprintf("use %s as %s ;", $use, $alias));
            }
        }

        $doc_block = $this->getDocblock() ;
        $writer
            ->writeln("/**")
            ->writeln( $doc_block ? $doc_block : sprintf(' * This code has been auto-generated by \\%s', __CLASS__) )
            ->writeln(" */\n")
            ;
        
        if( $this->isAbstract() ) {
            $writer->write('abstract ');
        } else if( $this->isFinal() ) {
            $writer->write('final ') ;
        }
        
        $writer       
            ->write('class '.  $shortName ) ;
           
        if( $this->getParentClassName() ) {
            $writer->write(' extends \\' . ltrim($this->getParentClassName(), '\\') ) ;
        }
       
        $writer->writeln(" {\n")
            ->indent()
        ;

        foreach($this->_traits as $_trait) {
            if( !trait_exists($_trait) ) {
                throw new \Exception ;
            }
            $writer->writeln(' use \\' . ltrim($_trait, '\\') . ';' ) ;
        }

        foreach($this->getConstants() as $name => $value ) {
            $writer->writeln( sprintf('const %s = %s;', $name, var_export($value, 1) )) ;
        }

        /**
         * @var $property PhpProperty
         */
        foreach( $this->getProperties() as $property ) {
            $property->writeCache($writer) ;
        }
        
        foreach($this->lazy_properties as $visible => $visible_values ) {
            foreach($visible_values as $name => $value ) {
                $writer->writeln( sprintf("\n%s $%s = %s ;", $visible, $name, PhpHelper::compilePropertyValue($value) )) ;
            }
        }
        
        if( $this->lazy_writer ) {
            $_wakeup_method   = $this->getMethod('__wakeup') ;
            $this->lazy_writer->writeln(  $_wakeup_method->getBody() ) ;
            $_wakeup_method->setBody( $this->lazy_writer->getContent() ) ;
        }

        /**
         * @var $method PhpMethod
         */
        foreach( $this->getMethods() as $method ) {
            
            if( $method instanceof PhpMethod) {
                $method->flushLazyCode() ;
                $_body  = $method->getWriter()->getContent() ;
            } else {
                $_body  = $method->getBody() ;
            }
            
            $writer->write("\n") ;
            if( $method->getDocblock() ) {
                $writer->writeln( $method->getDocblock() ) ;
            }
            if( $method->isFinal() ) {
                $writer ->write('final ') ;
            }
            $writer
                    ->write( $method->getVisibility() ) 
                    ->write( ' function ' ) 
                    ->write( $method->getName() ) 
                    ;
            $ps = $method->getParameters()  ;

            if( empty($ps) ) {
                $writer->write('()') ;
            } else {
                $writer->write('(');
                foreach( $method->getParameters() as $i => $p) {
                    if( $p->getType() ) {
                        if( in_array( $p->getType(), array('mixed') ) ) {
                            $writer->write( '/** @var ' . $p->getType() . ' */') ;
                        } else {
                            $writer->write(  $p->getType() . ' ') ;
                        }
                    }
                    if( $p->isPassedByReference() ) {
                        $writer->write(' & ') ;
                    }
                    $writer
                            ->write(' $')
                            ->write( $p->getName() )
                            ;
                    if( $p->hasDefaultValue() ) {
                        $writer->write(' = ' .  json_encode( $p->getDefaultValue() ) ) ;
                    }
                    if( $i < count($ps) - 1 ) {
                        $writer->write(", ");
                    }
                }
                
                $writer->write(')');
            }
            
            $writer
                    ->writeln( '{' )
                        ->indent()
                        ->write( $_body )
                        ->outdent()
                    ->writeln("}")
                    ;
        }
        
        $writer
                ->outdent()
                ->writeln('}') ;
        
        $content    = PhpHelper::decompilePhpCode($writer->getContent()) ;

        $_class_dir  = dirname($_class_file) ;
        if( !file_exists( $_class_dir) ) {
            if( !@mkdir( $_class_dir, 0755, true) ) {
                throw new \Exception( sprintf("mkdir(%s) error!", $_class_dir));
            }
        }

        PhpHelper::write_file( $_class_file, $content ) ;
        return $_class_file ;
    }


}