<?php
// IntegerTransformer.php

namespace Dormilich\ARIN\Transformers;

/**
 * Convert integer values.
 */
class IntegerTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform( $value )
    {
        // TRUE is considered 1
        if ( is_bool( $value ) ) {
            return $value;
        }

        $int = filter_var( $value, FILTER_VALIDATE_INT );

        return is_int( $int ) ? (string) $int : $value;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform( $value )
    {
        return is_numeric( $value ) ? (int) $value : $value;
    }
}
