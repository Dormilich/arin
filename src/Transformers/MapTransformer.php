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
     * Multiple input values may refer to the same XML value.
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
     * If an XML value maps to multiple PHP values, the first one found in the 
     * array is used.
     * 
     * @param string|NULL $value XML data.
     * @return mixed Output data.
     */
    public function reverseTransform( $value )
    {
        $key = array_search( $value, $this->map, true );

        return $key === false ? $value : $key;
    }
}
