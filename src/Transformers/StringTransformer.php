<?php
// StringTransformer.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Primary;

/**
 * Convert input to string and return XML data unchanged.
 */
class StringTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform( $value )
    {
        if ( is_bool( $value ) ) {
            $value = var_export( $value, true );
        }
        elseif ( is_scalar( $value ) ) {
            $value = (string) $value;
        }
        elseif ( $value instanceof Primary ) {
            $value = $value->getHandle();
        }
        elseif ( is_object( $value ) and method_exists( $value, '__toString' ) ) {
            $value = (string) $value;
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
}
