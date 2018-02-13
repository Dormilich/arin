<?php
// RegExp.php

namespace Dormilich\ARIN\Validators;

/**
 * Test a value against a regular expression.
 */
class RegExp
{
    /**
     * @var string[] The possible exact values allowed.
     */
    protected $pattern;

    /**
     * Create validator with the allowed values. The values may be of any type 
     * as long as they can be converted to a string.
     * 
     * @param array $setup The configuration for the validator.
     * @return self
     */
    public function __construct( array $setup )
    {
        if ( isset( $setup[ 'pattern' ] ) ) {
            $this->pattern = (string) $setup[ 'pattern' ];
        }

        $this->testPattern();
    }

    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param scalar $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        return 1 === preg_match( $this->pattern, $value );
    }

    /**
     * Test if the regular expression itself is valid.
     * 
     * @return void
     * @throws LogicException
     */
    protected function testPattern()
    {
        set_error_handler( function ( $code, $message ) {
            restore_error_handler();
            throw new \LogicException( $message, $code );
        } );
        preg_match( $this->pattern, NULL );
        restore_error_handler();
    }
}
