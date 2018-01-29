<?php
// ClassList.php

namespace Dormilich\ARIN\Validators;

/**
 * Test a value against a predefined set of classes.
 */
class ClassList extends Choice
{
    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param object $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        if ( ! is_object( $value ) ) {
            return false;
        }

        return in_array( get_class( $value ), $this->choices, true );
    }
}
