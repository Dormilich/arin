<?php
// BooleanTransformer.php

namespace Dormilich\ARIN\Transformers;

/**
 * Convert boolean expressions.
 */
class BooleanTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform( $value )
    {
        $bool = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

        return is_bool( $bool ) ? var_export( $bool, true ) : $value;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform( $value )
    {
        if ( strcasecmp( 'true', $value ) === 0 ) {
            return true;
        }
        if ( strcasecmp( 'false', $value ) === 0 ) {
            return false;
        }
        return $value;
    }
}
