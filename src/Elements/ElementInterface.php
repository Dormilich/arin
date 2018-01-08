<?php
// ElementInterface.php

namespace Dormilich\ARIN\Elements;

/**
 * An Element refers to either a single XML element having a text value 
 * or a collection of other elements.
 */
interface ElementInterface
{
    /**
     * Get the data of the element. This may be a string or an array of 
     * strings.
     * 
     * @return string|string[]
     */
    public function getValue();

    /**
     * Set the value of the element. If the element is an array type, delete 
     * previously set values. If the element is a Payload then the behaviour 
     * is implementation dependent and may throw an exception.
     * 
     * @param mixed $value Element value.
     * @return void|self
     */
    public function setValue( $value );
}
