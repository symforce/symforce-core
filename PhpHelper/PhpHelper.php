<?php

namespace Symforce\CoreBundle\PhpHelper ;


class PhpHelper {

    /**
     * @param string $class_name
     * @return null|string
     * @throws \Exception
     */
    static public function findFileByClassName($class_name) {

        static $_psr0_map   = null ;
        static $_psr4_map   = null ;
        if( null === $_psr0_map ) {
            $_psr_dir   = dirname( (new \ReflectionClass('Composer\\Autoload\\ClassLoader'))->getFileName() )  ;

            $_psr0_file = $_psr_dir . '/autoload_namespaces.php' ;
            if( !file_exists($_psr0_file) ) {
                throw new \Exception(sprintf("psr0 file(%s) not exits!", $_psr0_file)) ;
            }
            $_psr0_map  = include( $_psr0_file ) ;

            $_psr4_file = $_psr_dir . '/autoload_psr4.php' ;
            if( !file_exists($_psr4_file) ) {
                throw new \Exception(sprintf("psr4 file(%s) not exits!", $_psr4_file)) ;
            }
            $_psr4_map   = include( $_psr4_file ) ;
        }

        $_class_file = null ;
        $_class_psr_namespace = null ;

        foreach($_psr4_map as $_namespace => $_namespace_dir ) if( !empty($_namespace) && !empty($_namespace_dir) ) {
            $_pos = strpos($class_name, $_namespace) ;
            if( 0 === $_pos ) {
                if( !$_class_psr_namespace || strlen($_namespace) > strlen($_class_psr_namespace) ) {
                    $_class_psr_namespace  = $_namespace ;
                    $_class_file = $_namespace_dir[0] . '/' . str_replace('\\', '/', substr($class_name, strlen($_namespace) ) ) . '.php' ;
                }
            }
        }

        foreach($_psr0_map as $_namespace => $_namespace_dir ) if( !empty($_namespace) && !empty($_namespace_dir) ) {
            $_pos = strpos($class_name, $_namespace) ;
            if( 0 === $_pos ) {
                if( !$_class_psr_namespace || strlen($_namespace) > strlen($_class_psr_namespace) ) {
                    $_class_psr_namespace  = $_namespace ;
                    $_class_file = $_namespace_dir[0] . '/' . str_replace('\\', '/', $class_name ) . '.php' ;
                }
            }
        }

        if( !$_class_file ) {
            throw new \Exception(sprintf("can not resolve file for class(%s) by psr4 rule!", $class_name ) ) ;
        }

        return $_class_file ;
    }

    static public function isBaseType($type) {
        return in_array( strtolower($type), array(
            'null', 'void',
            'bool', 'boolean',  'number', 'int', 'integer', 'float', 'double', 'string',
            'array', 'object', 'resource', 'callable', 'mixed' ) );
    }

    static public function isConstants($const) {
        return in_array($const, array('__CLASS__', '__DIR__', '__FILE__', '__FUNCTION__', '__LINE__', '__METHOD__', '__NAMESPACE__', '__TRAIT__') );
    }

    static public function isKeywords($key) {
        return in_array($key, array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor'));
    }

    static public function isSQLKeywords($key) {
        return in_array( strtolower($key), array(
            'database' , 'table' , 'view',
            'drop' , 'create' , 'alter' , 'default',
            'primary', 'foreign', 'key', 'index', 'sequence', 'auto', 'increment',
            'select' ,  'update' , 'delete',
            'into', 'as',  'join' , 'from' , 'union' ,  'with',
            'left', 'right' , 'inner',
            'where' , 'not', 'in' , 'null' , 'values',
            'group', 'by', 'having', 'order', 'desc', 'asc', 'limit' ,
        )) ;
    }

    static public function isVariableName( $name ) {
        return preg_match('/^[a-z\_][a-z0-9\_]*[a-z0-9]$/', $name ) ;
    }

    static public function isPropertyName( $name ) {
        if( self::isKeywords($name) ) {
            return false ;
        }
        return preg_match('/^[a-z\_][a-z0-9\_]*[a-z0-9]$/', $name) ;
    }

    static public function isClassName( $name ) {
        if( self::isKeywords($name) || self::isBaseType($name) ) {
            return false ;
        }
        return self::isIdentifier($name) ;
    }

    static public function isIdentifier( $id ) {
        return preg_match('/^[a-z][a-z0-9\_]*[a-z0-9]$/', $id) ;
    }

    static public function write_file($path, $content ) {
        $need_flush = true ;
        if( file_exists($path) ) {
            $_content = file_get_contents($path) ;
            if( $_content === $content ) {
                $need_flush = false ;
            }
        }
        if( $need_flush ) {
            if (false === @file_put_contents( $path , $content ) ) {
                throw new \RuntimeException('Unable to write file ' . $path );
            }
        }
    }

    static public function compilePhpCode( $code ){
        return '#php{% ' . $code . ' %}' ;
    }

    static public function decompilePhpCode( $code ){
        return preg_replace_callback( '/\'\#php\{\%\s(.+?)\s\%\}\'/s' , function($m){
            return stripslashes($m[1]) ;
        } , $code ) ;
    }

    static public function compilePropertyValue( $object ) {
        if(is_object($object)) {
            return sprintf('unserialize(%s)', var_export(serialize($object), 1) ) ;
        } else if( is_array( $object) ) {
            return var_export($object, 1) ;
        } else {
            return json_encode($object) ;
        }
    }

    static public function camelize($string)
    {
        return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '_' : '').strtoupper($match[2]);
        }, $string ) ;
    }

    static public function deCamelize($string){
        return ltrim(preg_replace_callback('/[A-Z]/', function ($match) {
            return '_' . strtolower($match[0]) ;
        }, $string ), '_' ) ;
    }

    static public function humanize($text)
    {
        return ucfirst(trim(strtolower(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $text))));
    }
}