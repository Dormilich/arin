<?php
// Range.php

namespace Dormilich\ARIN\Validators;

/**
 * Test if a value represents an integer in a given range. Cf. ctype_digit() for testing
 */
class Range
{
    /**
     * @var integer Lower number limit.
     */
    protected $min;

    /**
     * @var integer Upper number limit.
     */
    protected $max;

    /**
     * Create validator with the appropriate value limits.
     * 
     * @param array $setup The configuration for the validator.
     * @return self
     */
    public function __construct( array $setup = [] )
    {
        if ( isset( $setup[ 'min' ] ) ) {
            $this->min = (int) $setup[ 'min' ];
        }
        if ( isset( $setup[ 'max' ] ) ) {
            $this->max = (int) $setup[ 'max' ];
        }
    }

    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param scalar $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        $number = filter_var( $value, FILTER_VALIDATE_INT, $this->getOptions() );

        return is_int( $number );
    }

    /**
     * Create the options array for further configuring filter_var().
     * 
     * @return array
     */
    protected function getOptions()
    {
        $options = [];

        if ( is_int( $this->min ) ) {
            $options[ 'options' ][ 'min_range' ] = $this->min;
        }
        if ( is_int( $this->max ) ) {
            $options[ 'options' ][ 'max_range' ] = $this->max;
        }

        return $options;
    }
}
