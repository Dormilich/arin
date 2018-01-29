<?php
// NamedElement.php

namespace Dormilich\ARIN\Validators;

use Dormilich\ARIN\Elements\Element;

/**
 * Test a value that it is an element and that is has the required name.
 */
class NamedElement
{
    /**
     * @var string The element's required name.
     */
    protected $name;

    /**
     * @param array $setup The configuration for the validator.
     * @return self
     */
    public function __construct( array $setup = [] )
    {
        if ( isset( $setup[ 'name' ] ) ) {
            $this->name = (string) $setup[ 'name' ];
        }
    }

    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param Element $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        if ( $value instanceof Element ) {
            return $value->getName() === $this->name;
        }

        return false;
    }
}
