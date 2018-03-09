<?php
// HandleTransformer.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Primary;
use Dormilich\ARIN\Payloads\Customer;
use Dormilich\ARIN\Payloads\Net;
use Dormilich\ARIN\Payloads\Org;
use Dormilich\ARIN\Payloads\Poc;

/**
 * Convert input to string and return a primary payload object.
 */
class HandleTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform( $value )
    {
        if ( $value instanceof Primary ) {
            $value = $value->getHandle();
        }
        elseif ( is_object( $value ) and method_exists( $value, '__toString' ) ) {
            $value = (string) $value;
        }

        if ( is_string( $value ) ) {
            $value = strtoupper( $value );
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform( $value )
    {
        if ( preg_match('/^NET-(\d{1,3}-){4}\d$/', $value ) ) {
            return new Net( $value );
        }
        if ( preg_match('/^NET6-([0-9A-F]{1,4}-){1,8}\d$/', $value ) ) {
            return new Net( $value );
        }
        if ( preg_match('/^C\d+$/', $value ) ) {
            return new Customer( $value );
        }
        if ( preg_match('/^[A-Z]+\d*-ARIN$/', $value ) ) {
            return new Poc( $value );
        }
        if ( preg_match('/^[A-Z0-9]+(-\d+)?$/', $value ) ) {
            return new Org( $value );
        }

        return $value;
    }
}
