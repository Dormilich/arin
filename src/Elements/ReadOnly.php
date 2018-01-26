<?php
// ReadOnly.php

namespace Dormilich\ARIN\Elements;

use Dormilich\ARIN\Exceptions\ValidationException;

/**
 * A read-only element only allows to set its content once 
 * (usually when parsing the XML data from the API). 
 */
class ReadOnly extends Element
{
    /**
     * Set the text content of the element. Once the value is set, it cannot 
     * be modified and will issue a PHP warning if attempted. The value may be 
     * any type that can be stringified.
     * 
     * @param string $value New element text content.
     * @return self
     */
    public function setValue( $value )
    {
        if ( NULL === $this->value ) {
            return parent::setValue( $value );
        }

        if ( $this->transform( $value ) === $this->value ) {
            return $this;
        }

        $msg = 'The [%s] element must not be modified once it is set.';
        throw new ValidationException( sprintf( $msg, $this->getName() ) );
    }
}
