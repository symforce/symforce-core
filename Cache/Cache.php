<?php

namespace Symforce\CoreBundle\Cache;


class Cache {

    /**
     * @var \Doctrine\Common\Cache\CacheProvider
     */
    protected $engine ;
    protected $storage = array();
    protected $time = null;
    protected $default_ttl ;
    protected $prefix ;

    protected $auto_flush ;

    /**
     * @var \Zumba\Util\JsonSerializer
     */
    protected $serializer ;

    protected static $availableEngines = array();

    public function __construct(\Doctrine\Common\Cache\CacheProvider $engine, $ttl = 3600 , $prefix = null ) {

        $this->time = time();

        $this->serializer = new \Zumba\Util\JsonSerializer();

        $this->engine   = $engine ;
        $this->default_ttl  = $ttl ;
        if( !$prefix ) {
            $prefix = $engine->getNamespace() ;
        }
        $this->prefix =  $prefix ;

        $this->auto_flush   = true ;
    }

    public function enableAutoFlush() {
        $this->auto_flush   = true ;
        return $this ;
    }

    public function disableAutoFlush() {
        $this->auto_flush   = false ;
        return $this ;
    }

    private function getCacheId($key) {
        return $this->prefix . '_id_' . $key ;
    }

    private function getTagId($key) {
        return  $this->prefix . '_tag_' .  $key ;
    }

    protected function __retrieve($id) {
        $content    = $this->engine->fetch( $id ) ;
        if( false !== $content ) {
            $content    = $this->serializer->unserialize($content) ;
        }
        return $content ;
    }

    protected function __store($id, & $value , $ttl ) {
        $this->engine->save($id, $this->serializer->serialize($value) , $ttl ) ;
    }

    public function fetch($key, \Closure $callback = null , array $tags = null, $ttl = null , $auto_flush = true ) {
        if ( !empty($this->storage[$key]) ) {
            return $this->storage[$key] ;
        }
        $id = $this->getCacheId($key) ;
        $content    = $this->__retrieve( $id ) ;
        if ( false === $content && $callback ) {
            $content = $callback($this, $key) ;
            $this->save($key, $content, $tags, $ttl, $auto_flush) ;
        }
        return $content ?: null;
    }

    public function save($key, $input, array $tags = null, $ttl = null , $auto_flush = true ){
        if ( $tags ) {
            $this->addKeyToTags($key, $tags ) ;
        }
        if( null === $ttl ) {
            $ttl    = $this->default_ttl ;
        }
        $id = $this->getCacheId($key) ;
        $this->__store($id, $input, $ttl) ;
        if( $this->auto_flush && $auto_flush ) {
            $this->engine->flushAll() ;
        }
        $this->storage[$key] = $input ;
        return $input ;
    }

    public function delete($keys, $auto_flush = true ){
        $keys = (array) $keys ;
        foreach ($keys as $key)
            unset($this->storage[$key]);

        foreach($keys as $key ) {
            $id = $this->getCacheId($key) ;
            $this->engine->delete($id) ;
            unset($this->storage[$key]) ;
        }
        if( $this->auto_flush && $auto_flush ) {
            $this->engine->flushAll() ;
        }
        return $this;
    }

    public function deleteByTag($tag , $auto_flush = true ){
        $keys = $this->getKeysByTag( $tag );
        if ( count($keys) ) {
            $this->delete( $keys );
        }
        $this->deleteTag($tag);
        if( $this->auto_flush && $auto_flush ) {
            $this->engine->flushAll() ;
        }
        return $this;
    }

    public function deleteByTags(array $tags, $auto_flush = true){
        $keys = null;
        foreach($tags as $tag){
            $list = $this->getKeysByTag($tag);
            $keys = empty($keys) ? $list : array_intersect($keys, $list);
            $this->deleteTag($tag, false );
        }
        return $this->delete($keys, $auto_flush);
    }

    public function deleteAll($auto_flush = true){
        $this->engine->deleteAll() ;
        if( $this->auto_flush && $auto_flush ) {
            return $this->engine->flushAll() ;
        }
    }

    public function flush(){
        $this->storage = array() ;
        return $this->engine->flushAll() ;
    }

    protected function getKeysByTag($tag){
        $id = $this->getTagId($tag) ;
        $list   = $this->engine->fetch($id) ;
        if( ! $list ) {
            $list   = array() ;
        }
        return $list ;
    }

    protected function addKeyToTag($key, $tag) {
        $id = $this->getTagId($tag) ;
        $data   = $this->engine->fetch($id) ;
        if( false === $data ) {
            $data   = array() ;
        }
        $data[] = $key ;
        $this->engine->save($id, $data) ;
    }

    protected function addKeyToTags($key, $tags) {
        foreach ($tags as $tag)
            $this->addKeyToTag($key, $tag) ;
    }

    protected function deleteTag($tag) {
        $id = $this->getTagId($tag) ;
        $this->engine->delete($id) ;
    }

}