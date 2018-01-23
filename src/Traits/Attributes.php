<?php
// Attributes.php

namespace Dormilich\ARIN\Traits;

trait Attributes
{
    /**
     * @var array $attributes XML attibute definitions.
     */
    protected $attributes = [];

    /**
     * Getter for a generated attribute.
     * 
     * Note: RegRWS only uses element values, element attributes, and
     *       element values with generated attributes (DelegationKey).
     * 
     * @param string $name XML attribute name.
     * @return string|NULL Attribute value.
     */
    public function __get( $name )
    {
        if ( isset( $this->attributes[ $name ] ) ) {
            return $this->attributes[ $name ];
        }
        return NULL;
    }
}
