<?php
// DataValidation.php

namespace Dormilich\ARIN\Traits;

trait DataValidation
{
    /**
     * @var callable A callback to test if the final value matches the constraints.
     */
    protected $validator;

    /**
     * Set a new data validator.
     * 
     * @param callable $fn 
     * @return self
     */
    public function test( callable $fn )
    {
        $this->validator = $fn;

        return $this;
    }

    /**
     * Validate a (transformed) input value. 
     * 
     * @param mixed $value 
     * @return boolean
     */
    protected function validate( $value )
    {
        return call_user_func( $this->validator, $value );
    }
}
