<?php
// MapTransformer.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Exceptions\DataTransformationException;

/**
 * Transform the provided value based on a map.
 */
class MapTransformer implements DataTransformerInterface
{
    /**
     * @var array|ArrayAccess Dictionary.
     */
    protected $map = [];

    public function __construct( array $map )
    {
        $this->map = $map;
    }

    /**
     * Transform the data from the input format to the XML-compatible format.
     * 
     * @param mixed $value Input data.
     * @return mixed XML data.
     */
    public function transform( $value )
    {
        return isset( $this->map[ $value ] ) ? $this->map[ $value ] : $value;
    }

    /**
     * Transform the XML data into a convenient data format for handling in PHP.
     * 
     * @param string|NULL $value XML data.
     * @return mixed Output data.
     */
    public function reverseTransform( $value )
    {
        return array_search( $value, $this->map, true );
    }
}
