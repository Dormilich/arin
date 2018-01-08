<?php
// DataTransformation.php

namespace Dormilich\ARIN\Traits;

use Dormilich\ARIN\Transformers\DataTransformerInterface;

trait DataTransformation
{
    /**
     * @var callable A callback to apply pre-validation transformation.
     */
    protected $transformer;

    /**
     * Set a new data transformer. Consider setting a matching data validator as well.
     * 
     * @param DataTransformerInterface $transformer 
     * @return self
     */
    public function apply( DataTransformerInterface $transformer )
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Transforms a value according to the set transformer.
     * 
     * @param mixed $value 
     * @return mixed
     */
    protected function transform( $value )
    {
        return $this->transformer->transform( $value );
    }
}
