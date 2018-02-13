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
     * strings, depending on the element.
     * 
     * @return string|string[]
     */
    public function getValue();

    /**
     * Set the value of the element. If NULL is passed, delete the previous content.
     * 
     * @param mixed $value Element value.
     * @return void|self
     */
    public function setValue( $value );
}
