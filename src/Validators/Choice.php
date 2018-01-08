<?php
// Choice.php

namespace Dormilich\ARIN\Validators;

/**
 * Test a value against a predefined set of strings.
 */
class Choice
{
    /**
     * @var string[] The possible exact values allowed.
     */
    protected $choices = [];

    /**
     * Create validator with the allowed values. The values may be of any type 
     * as long as they can be converted to a string.
     * 
     * @param array $setup The configuration for the validator.
     * @return self
     */
    public function __construct( array $setup )
    {
        if ( isset( $setup[ 'choices' ] ) ) {
            $this->choices = array_map( 'strval', $setup[ 'choices' ] );
        }
    }

    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param scalar $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        return in_array( $value, $this->choices, true );
    }
}
