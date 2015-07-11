<?php

namespace Symforce\CoreBundle\PhpHelper ;


class PhpHelper {

    static public function isConstants($const) {
        return in_array($const, array('__CLASS__', '__DIR__', '__FILE__', '__FUNCTION__', '__LINE__', '__METHOD__', '__NAMESPACE__', '__TRAIT__') );
    }

    static public function isKeywords($key) {
        return in_array($key, array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor'));
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
        if( self::isKeywords($name) ) {
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
            throw new \Exception('can not encode object') ;
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

    static public function humanize($text)
    {
        return ucfirst(trim(strtolower(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $text))));
    }
}