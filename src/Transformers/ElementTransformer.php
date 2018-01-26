<?php
// ElementTransformer.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Exceptions\ValidationException;

/**
 * Convert input into an Element instance.
 */
class ElementTransformer implements DataTransformerInterface
{
    /**
     * @var Element A preconfigured element instance.
     */
    protected $element;

    /**
     * @param Element $element A preconfigured element instance.
     * @return self
     */
    public function __construct( Element $element )
    {
        // just in case
        $element->setValue( NULL );

        $this->element = $element;
    }

    /**
     * @inheritDoc
     */
    public function transform( $value )
    {
        if ( ! $value instanceof Element ) {
            $value = $this->toElement( $value );
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform( $value )
    {
        return $value;
    }

    /**
     * Convert a (stringifiable) value into an Element object.
     * 
     * @param mixed $value 
     * @return mixed|Element
     */
    protected function toElement( $value )
    {
        if ( ! is_string( $value ) ) {
            $value = (new StringTransformer)->transform( $value );
        }

        if ( is_string( $value ) ) {
            $value = $this->createElement( $value );
        }

        return $value;
    }

    /**
     * Create a new element instance from a (scalar) value.
     * 
     * @param mixed $value 
     * @return mixed|Element
     */
    protected function createElement( $value )
    {
        try {
            $e = clone $this->element;
            $e->setValue( $value );
            return $e;
        } catch ( ValidationException $e ) {
            return $value;
        }
    }
}
