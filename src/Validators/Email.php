<?php
// Email.php

namespace Dormilich\ARIN\Validators;

/**
 * Test a value against a predefined set of strings.
 */
class Email
{
    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param scalar $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        return false !== filter_var( $value, FILTER_VALIDATE_EMAIL );
    }
}
