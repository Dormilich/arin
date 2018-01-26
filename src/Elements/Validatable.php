<?php
// Validatable.php

namespace Dormilich\ARIN\Elements;

interface Validatable
{
    /**
     * Set a new data validator.
     * 
     * @param callable $fn 
     * @return self
     */
    public function test( callable $fn );
}
