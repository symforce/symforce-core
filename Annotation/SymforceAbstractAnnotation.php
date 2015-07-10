<?php

namespace Symforce\CoreBundle\Annotation;


abstract class SymforceAbstractAnnotation
{

    const SYMFORCE_GROUP_NAME = 0 ;
    const SYMFORCE_ANNOTATION_VALUE_PROPERTY = 0 ;

    /**
     * Constructor
     *
     * @param array $data Key-value for properties to be defined in this class
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if( property_exists( $this, $key ) ) {
                $this->$key = $value;
            } else {
                $this->__set($key, $value ) ;
            }
        }
    }

    /**
     * Error handler for unknown property accessor in Annotation class.
     *
     * @param string $name Unknown property name
     *
     * @throws \BadMethodCallException
     */
    public function __get($name)
    {
        throw new \BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
        );
    }

    /**
     * Error handler for unknown property mutator in Annotation class.
     *
     * @param string $name Unkown property name
     * @param mixed $value Property value
     *
     * @throws \BadMethodCallException
     */
    public function __set($name, $value)
    {
        if( $name === 'value' ) {
            $property = static::SYMFORCE_ANNOTATION_VALUE_PROPERTY ;
            if( $property ) {
                $this->$property = $value ;
                return ;
            }
        }

        throw new \BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this) )
        );
    }

    public function getArrayKeyProperty() {
        return null ;
    }
}