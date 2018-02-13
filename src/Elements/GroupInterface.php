<?php
// ElementInterface.php

namespace Dormilich\ARIN\Elements;

/**
 * An Element refers to either a single XML element having a text value 
 * or a collection of other elements.
 */
interface GroupInterface extends ElementInterface
{
    /**
     * Add a value to the element without deleting the previous content.
     * 
     * @param mixed $value Element value.
     * @return void|self
     */
    public function addValue( $value );
}
