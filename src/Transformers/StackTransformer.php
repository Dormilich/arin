<?php
// StackTransformer.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Exceptions\DataTransformationException;

/**
 * Consecutively transform a value according to the configured transformers.  
 */
class StackTransformer implements DataTransformerInterface, \Countable
{
    /**
     * @var DataTransformerInterface[]
     */
    protected $stack = [];

    /**
     * Transform the data from the input format to the XML-compatible format.
     * 
     * @param mixed $value Input data.
     * @return mixed XML data.
     */
    public function transform( $value )
    {
        return array_reduce( $this->stack, function ( $val, $trans ) {
            return $trans->transform( $val );
        }, $value );
    }

    /**
     * Transform the XML data into a convenient data format for handling in PHP.
     * 
     * @param string|NULL $value XML data.
     * @return mixed Output data.
     */
    public function reverseTransform( $value )
    {
        return array_reduce( array_reverse( $this->stack ), function ( $val, $trans ) {
            return $trans->reverseTransform( $val );
        }, $value );
    }

    /**
     * Add a transformer to the stack.
     * 
     * @param DataTransformerInterface $transformer 
     * @return self
     */
    public function push( DataTransformerInterface $transformer )
    {
        $this->stack[] = $transformer;

        return $this;
    }

    /**
     * Remove the last transformer from the stack.
     * 
     * @return DataTransformerInterface
     */
    public function pop()
    {
        return array_pop( $this->stack );
    }

    /**
     * @see http://php.net/Countable
     * @return integer
     */
    public function count()
    {
        return count( $this->stack );
    }
}
