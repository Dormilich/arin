<?php
// Generated.php

namespace Dormilich\ARIN\Elements;

/**
 * A generated element cannot be cloned since the values are solely provided by 
 * the ARIN DB (e.g. IDs or modification timestamps). For that reason, generated 
 * elements are always read-only as well.
 */
class Generated extends ReadOnly
{
    /**
     * Reset the element’s contents on cloning.
     * 
     * @return void
     */
    public function __clone()
    {
        $this->value = NULL;
        $this->attributes = [];
    }

    /**
     * A generated value does not need input transformation/validation. 
     * 
     * @param mixed $value 
     * @return string
     */
    protected function convert( $value )
    {
        return (string) $value;
    }
}
