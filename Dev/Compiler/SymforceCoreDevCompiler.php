<?php

namespace Symforce\CoreBundle\Dev\Compiler ;

use Symforce\CoreBundle\PhpHelper ;

final class SymforceCoreDevCompiler {

    const TAG_NAME =  'sf.dev.builder' ;

    private $traits = array() ;
    private $callback = array() ;

    public function addDevBuilder($id, array & $attributes){
        $ok = false ;
        if( isset($attributes['trait']) ) {
            if( !trait_exists($attributes['trait']) ) {
                throw new \Exception( sprintf("service(%s, tags:{name:%s, trait:%s}) trait not exists!", $id, self::TAG_NAME, var_export($attributes['trait'], 1) ) ) ;
            }
            $this->traits[] = $attributes['trait'] ;
            $ok = true ;
        }

        if( isset($attributes['compile']) ) {
            $this->callback = $attributes['compile'] ;
            $ok = true ;
        }

        if( !$ok ){
            throw new \Exception( sprintf("service(%s, tags:{name:%s}) require trait or compile", $id, self::TAG_NAME ) ) ;
        }
    }

    public function compile( $root_dir ) {
        $class = new PhpHelper\PhpClass() ;
        $class
            ->setName('Dev')
            ->setFinal(true)
            ->setParentClassName('Symforce\\CoreBundle\\Dev\\SymforceCoreDev')
            ;

        foreach($this->traits as $_trait ) {
            $class->addTrait($_trait) ;
        }

        $file = sprintf('%s/../src/Dev.php', $root_dir) ;
        $class->writeCache( $file ) ;
    }

}