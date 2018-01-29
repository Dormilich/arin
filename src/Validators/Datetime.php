<?php
// Datetime.php

namespace Dormilich\ARIN\Validators;

/**
 * Test a value against a predefined date format.
 */
class Datetime
{
    /**
     * @var string
     */
    protected $format = 'c';

    /**
     * Create validator with the allowed values. The values may be of any type 
     * as long as they can be converted to a string.
     * 
     * @param array $setup The configuration for the validator.
     * @return self
     */
    public function __construct( array $setup = [] )
    {
        if ( isset( $setup[ 'format' ] ) ) {
            $this->format = (string) $setup[ 'format' ];
        }
    }

    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param scalar $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        return is_object( date_create_from_format( $this->format, $value ) );
    }
}
