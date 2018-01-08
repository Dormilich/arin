<?php
// CallbackTransformer.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Exceptions\DataTransformationException;

/**
 * Use functions for transformation. 
 */
class CallbackTransformer implements DataTransformerInterface
{
    /**
     * @var callable Transformer to XML.
     */
    protected $up;

    /**
     * @var callable Transformer from XML.
     */
    protected $down;

    public function __construct( callable $up, callable $down )
    {
        $this->up = $up;
        $this->down = $down;
    }

    /**
     * Transform the data from the input format to the XML-compatible format.
     * 
     * @param mixed $value Input data.
     * @return mixed XML data.
     */
    public function transform( $value )
    {
        return call_user_func( $this->up, $value );
    }

    /**
     * Transform the XML data into a convenient data format for handling in PHP.
     * 
     * @param string|NULL $value XML data.
     * @return mixed Output data.
     */
    public function reverseTransform( $value )
    {
        return call_user_func( $this->down, $value );
    }
}
