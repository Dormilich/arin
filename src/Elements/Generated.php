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
     * A generated value does not need input transformation. 
     * 
     * @param mixed $value 
     * @return mixed
     */
    protected function transform( $value )
    {
        return $value;
    }

    /**
     * A generated value does not need validation.
     * 
     * @param mixed $value 
     * @return boolean
     */
    protected function validate( $value )
    {
        return true;
    }
}
