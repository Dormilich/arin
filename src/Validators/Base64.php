<?php
// Base64.php

namespace Dormilich\ARIN\Validators;

/**
 * Test a value if it is base64 encoded.
 */
class Base64
{
    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param scalar $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        return strlen( $value ) and false !== base64_decode( $value, true );
    }
}
