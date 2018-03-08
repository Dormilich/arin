<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Validators\RegExp;

/**
 * This is a meta object for use in the RoaData payload.
 */
class RoaPrefix extends Payload
{
    /**
     * @param NetworkInterface|string|NULL $cidr 
     * @return type
     */
    public function __construct( $cidr = NULL )
    {
        $this->init();
        $this->set( 'cidr', $cidr );
    }

    /**
     * Return the string for a prefix in the Roa data.
     * 
     * @return string
     */
    public function __toString()
    {
        $prefix = str_replace( '/', '|', $this->attr( 'cidr' ) );
        $prefix .= '|' . $this->attr( 'maxLength' );

        return $prefix;
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->define( NULL, new Element( 'cidr' ) )
            ->test( new RegExp( [ 'pattern' => '/^[0-9a-f:.]+\/\d+$/i' ] ) );
        // must not be smaller than the CIDR's prefix length value
        $this->define( NULL, new Element( 'maxLength' ) )
            ->test( 'ctype_digit' );
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        return $this->attr( 'cidr' )->isValid();
    }
}
