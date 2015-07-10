<?php

namespace Symforce\CoreBundle\PhpHelper ;


class PhpHelper {

    static public function isVariableName( $name ) {
        return preg_match('/^[a-z\_][a-z0-9\_]*[a-z0-9]$/', $name ) ;
    }

    static public function isPropertyName( $name ) {
        return preg_match('/^[a-z\_][a-z0-9\_]*[a-z0-9]$/', $name) ;
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