<?php
// NonTransformer.php

namespace Dormilich\ARIN\Transformers;

/**
 * Convert not at all.
 */
class NonTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform( $value )
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform( $value )
    {
        return $value;
    }
}
