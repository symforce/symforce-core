<?php

namespace Symforce\CoreBundle\Dev ;

use Symfony\Component\DependencyInjection\ContainerInterface ;
use Symforce\CoreBundle\PhpHelper\PhpHelper ;

abstract class SymforceCoreDev {

    /**
     * @var ContainerInterface
     */
    static private $_container ;

    static public function setContainer(ContainerInterface $_container){
        if( null === self::$_container ) {
            self::$_container = $_container ;
        }
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    static public function doctrine() {
        return self::$_container->get('doctrine') ;
    }

    /**
     * @param string $namespace
     * @return \Symforce\CoreBundle\Cache\Cache
     */
    static public function cache( $namespace, $ttl = 3600 ) {
        static $_cached = array() ;
        if( isset($_cached[$namespace]) ) {
            return $_cached[$namespace] ;
        }
        $engine    =  self::$_container->get('doctrine_cache.providers.app_cache') ;

        /**
         * @var $_engine \Doctrine\Common\Cache\CacheProvider
         */
        $_engine    = clone $engine ;
        $_engine->setNamespace($namespace) ;

        $cache  = new \Symforce\CoreBundle\Cache\Cache($_engine, $ttl, $namespace ) ;
        $_cached[$namespace]  = $cache ;
        return $cache ;
    }

    /**
     * @return \Monolog\Logger
     */
    static public function logger($key, $name = null, $rotating = false) {
        static $loggers	= array() ;
        if( !isset( $loggers[$key]) ) {
            $root_dir = self::$_container->getParameter('kernel.root_dir') ;
            $env_name   = self::$_container->getParameter('kernel.environment');
            $_dir	= sprintf('%s/logs/%s/',$root_dir, $env_name ) ;
            if( !file_exists($_dir) || !is_dir($_dir) ) {
                if( !@mkdir( $_dir, 0755, true)  ) {
                    throw new Exception( sprintf('mkdir(%s) error!', $_dir ) ) ;
                }
            }
            $_file	= preg_replace_callback('/\W+/', function($m){
                $s	=  preg_replace('/[^\/]+/', '_', $m[0]) ;
                $s	=  preg_replace('/\/+/', '/', $s ) ;
                return $s ;
            } , trim( trim($key) , '/' ) )  ;

            if( substr_count($_file, '/') > 0 ) {
                $dir_name	= dirname( $_file ) ;
                $_full_path	= $_dir . $dir_name ;
                if( !@mkdir( $_full_path, 0755, true) ) {
                    throw new \Exception( sprintf("mkdir(%s) error!", $_full_path));
                }
            }

            $_full_path	= $_dir . $_file . '.log' ;
            $logger	= new \Monolog\Logger( $name ) ;
            if( $rotating ) {
                $handler = new \Monolog\Handler\RotatingFileHandler( $_full_path ) ;
            } else {
                $handler = new \Monolog\Handler\StreamHandler( $_full_path ) ;
            }
            $logger->pushHandler( $handler ) ;
            $loggers[$key] = $logger ;
        }
        return $loggers[$key] ;
    }


    static public function debug(){
        self::print_callback() ;
        $writer = new \CG\Generator\Writer() ;
        $writer->indent() ;
        $args   = func_get_args() ;
        foreach($args as  $i => & $arg ) {
            $writer->write("\n$i -> ") ;
            $writer->indent() ;
            $visited    = array() ;
            self::export($writer, $visited, $arg, 4 ) ;
            $writer->outdent() ;
        }
        $writer->outdent() ;
        echo $writer->getContent(), "\n" ;
    }

    static public function dump( $object, $deep = 4 , $do_print = true ){
        if( !$do_print ) {
            ob_start() ;
        }
        self::print_callback() ;
        $writer = new \CG\Generator\Writer() ;
        $writer->indent() ;
        $writer->write("\n") ;
        $visited    = array() ;
        self::export($writer, $visited, $object, $deep ) ;
        $writer->outdent() ;
        echo $writer->getContent(), "\n" ;
        if( !$do_print ) {
            $out = ob_get_contents();
            ob_end_clean();
            return $out ;
        }
    }

    static private function export(\CG\Generator\Writer $writer, array & $visited , $value , $deep = 1 , $counter = 0x3ffff ) {
        $deep-- ;
        $counter-- ;
        if( is_object($value) ) {
            if( $value instanceof \DateTime ) {
                $writer->write(sprintf('\DateTime(%s, %s)', $value->format("Y/m/d H:i:s"), $value->getTimezone()->getName() ) ) ;
            } else if( $value instanceof \DateTimeZone ) {
                $writer->write(sprintf('\DateTimeZone(%s)', $value->getName() ) ) ;
            } else if( $value instanceof \Doctrine\ORM\PersistentCollection ) {
                $writer->write(sprintf('\Doctrine\ORM\PersistentCollection(%s, %s)', spl_object_hash($value), $value->getTypeClass()->getName() ) ) ;
            } else if( $value instanceof \Closure ) {
                $_rc = new \ReflectionFunction($value);
                $writer->write( sprintf('\Closure(%s, file:%s line:[%d,%s])',
                    spl_object_hash($value),
                    self::fixPath($_rc->getFileName()),
                    $_rc->getStartLine() ,
                    $_rc->getEndLine()
                )) ;
            } else {
                $oid = spl_object_hash($value) ;
                $object_class   = get_class($value) ;
                if( isset($visited[$oid]) ) {
                    $writer->write( sprintf("#%s(%s)", $object_class , $oid) );
                } else {
                    $visited[$oid]  = true ;
                    if( $deep > 0 ) {

                        $skip_properties    = array() ;
                        if( $value instanceof  \Doctrine\ORM\Proxy\Proxy ) {
                            $skip_properties  = array_merge(array(
                                '__initializer__' ,
                                '__cloner__' ,
                                '__isInitialized__' ,
                            ), $skip_properties ) ;
                        }

                        $writer->write(sprintf( "%s(%s) { \n", $object_class , $oid )) ;
                        $writer->indent() ;
                        $r = new \ReflectionClass( $object_class ) ;
                        $output = array();
                        foreach( $r->getProperties() as $p ) {
                            if( $p->isStatic() ) {
                                continue ;
                            }
                            if( $counter < 0 ) {
                                $writer->writeln("......") ;
                                break ;
                            }
                            $_p     = $p->getName() ;
                            if( in_array($_p, $skip_properties) ) {
                                continue;
                            }
                            $p->setAccessible( true ) ;
                            $_value = $p->getValue( $value ) ;
                            $writer->write( $_p . ' : ') ;
                            self::export($writer, $visited, $_value, $deep, $counter ) ;
                            $writer->write("\n") ;
                        }
                        $writer->outdent() ;
                        $writer->write("}");
                    } else {
                        $r = new \ReflectionClass( $object_class ) ;
                        $output = array() ;
                        foreach( $r->getProperties() as $p ) {
                            if( count($output) > 1 ) {
                                break ;
                            }
                            if( $p->isStatic() ) {
                                continue ;
                            }
                            $p->setAccessible( true ) ;
                            $_value     = $p->getValue( $value ) ;
                            if( is_object($_value) || is_array($_value) ) {
                                continue ;
                            }
                            $_p     = $p->getName() ;

                            if( 0 === strpos( $_p, '_') ) {
                                continue; ;
                            }

                            if(is_string($_value) ) {
                                if( strlen($_value) > 0xf ) {
                                    $output[ $_p ] = substr($_value, 0xc ) . '..' ;
                                } else {
                                    $output[ $_p ] = $_value ;
                                }
                            } else {
                                $output[ $_p ] = $_value ;
                            }
                        }

                        $writer->write( sprintf("%s(%s)", $object_class, $oid ) );
                        if( !empty($output) ) {
                            $writer
                                ->indent()
                                ->write( " = " . json_encode($output) )
                                ->outdent()
                            ;
                        }
                    }
                }
            }
        } else if( is_array($value) ) {
            if( $deep > 0 ) {
                $writer->writeln("array(");
                $writer->indent() ;
                foreach($value as $_key => & $_value ) {
                    if( $counter < 0 ) {
                        $writer->writeln("...") ;
                        break ;
                    }
                    $writer->write( $_key . ' => ') ;
                    self::export($writer, $visited, $_value, $deep, $counter ) ;
                    $writer->write("\n") ;
                }
                $writer->outdent() ;
                $writer->writeln(")");
            } else {
                $writer->write( sprintf("array( length = %s ) ", count($value) ) );
            }
        } else if( null === $value ) {
            $writer->write("null");
        } else if( is_string($value) ) {
            if(strlen($value) < 0x7f ) {
                $writer->write( var_export($value, 1) );
            } else {
                $writer->write( var_export(substr($value, 0, 0x7f) . '...' , 1) );
            }
            $writer->write(sprintf("%d", strlen($value) )) ;
        } else if(is_bool($value) ) {
            $writer->write(  $value ? 'true' : 'false' );
        } else if( is_numeric($value) ) {
            $writer->write( var_export($value, 1) );
        } else {
            $writer->write( sprintf("%s ( %s ) ", gettype($value), var_export($value, 1)) ) ;
        }
    }

    static private function print_callback() {
        $o = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
        $file   = self::fixPath( $o[1]['file'] ) ;
        $line   = $o[1]['line'] ;
        echo '#', $file, ":", $line;

        if( isset($o[2]) ) {
            $fn     = null ;
            if( isset($o[2]['class']) ) {
                $fn     = $o[2]['class'] .  $o[2]['type'] . $o[2]['function'] ;
            } else if( $o[2]['function'] ){
                $fn     = $o[2]['function'] ;
            }
            if( $fn ) {
                echo " @", $fn ;
            }
        }
    }

    static private function fixPath( $path ) {
        static $root_dir    = null ;
        if( null === $root_dir  ) {
            $root_dir   = 1 + strlen( dirname( __DIR__ ) ) ;
        }
        return substr($path, $root_dir ) ;
    }

    static public function isSimpleArray(array & $array) {
        $keys   = array_keys( $array );
        foreach ($keys as $i => $I ) {
            if( $i !== $I ) {
                return false ;
            }
        }
        return true ;
    }

    static public function merge(array & $a, array & $b) {
        foreach ($b as $key => & $value ) {
            if (isset($a[$key])) {
                if ( is_array($a[$key]) && is_array($value)) {
                    if( !self::isSimpleArray($a[$key]) || !self::isSimpleArray($b[$key]) ) {
                        self::merge($a[$key], $value);
                    } else {
                        foreach($value as $_key => $_value ) {
                            $a[$key][] = $_value ;
                        }
                    }
                    continue ;
                }
            }
            $a[$key] = $value;
        }
    }

    static public function type($var) {
        if(is_object($var) ) {
            return get_class($var);
        }
        return gettype($var);
    }
}