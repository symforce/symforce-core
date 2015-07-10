<?php

namespace Symforce\CoreBundle\PhpHelper ;

trait SymforceCoreDevTrait {

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
                if( !@mkdir( $_dir, 0755 )  ) {
                    throw new Exception( sprintf('mkdir `%s` error!', $_dir ) ) ;
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
                self::mkdir($_full_path) ;
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

}