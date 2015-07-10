<?php

namespace Symforce\CoreBundle\Assets;


final class SymforceAssetsResource {

    private $_path ;
    private $_target ;

    private $_extension ;

    public function getPath(){
        return $this->_path ;
    }

    public function getTarget(){
        return $this->_target ;
    }

    public function getExtension(){
        if( is_string($this->_extension) ) {
            $this->_extension = preg_split('/\s*,\s*\.?/', preg_replace('/^\W*|\W*$/', '', $this->_extension) ) ;
        } elseif( !is_array($this->_extension) ){
            $this->_extension = null ;
        }
        return $this->_extension ;
    }

    public function setPath( $value ){
        $this->_path = $value ;
    }

    public function setTarget( $value ){
        $this->_target = $value ;
    }

    public function setExtension( $value ){
         $this->_extension = $value ;
    }

}