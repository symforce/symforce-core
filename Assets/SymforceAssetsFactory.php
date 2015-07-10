<?php

namespace Symforce\CoreBundle\Assets;

class SymforceAssetsFactory {

    private $_tagName ;
    private $_resources = array() ;

    public function setTagName( $name ) {
        $this->_tagName = $name ;
    }

    public function getTagName() {
        return $this->_tagName  ;
    }

    /**
     * @param string $service_id
     * @param SymforceAssetsResource $resource
     */
    public function addAssetsResource($service_id, SymforceAssetsResource $resource) {
        $this->_resources[ $service_id ] = $resource ;
    }

    public function getResources() {
        return $this->_resources ;
    }
}