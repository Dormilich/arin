<?php
// DataTransformerInterface.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Exceptions\DataTransformationException;

/**
 * A data transformer applies data type conversion between the input data and 
 * the XML data if a conversion can be applied. 
 */
interface DataTransformerInterface
{
    /**
     * Transform the data from the input format to the XML-compatible format.
     * 
     * @param mixed $value Input data.
     * @return mixed XML data.
     */
    public function transform( $value );

    /**
     * Transform the XML data into a convenient data format for handling in PHP.
     * 
     * @param string|NULL $value XML data.
     * @return mixed Output data.
     */
    public function reverseTransform( $value );
}
