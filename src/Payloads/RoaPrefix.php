<?php
// RoaPrefix.php 

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Validators\RegExp;

/**
 * This is a meta object for use in the RoaData payload.
 */
class RoaPrefix extends Payload
{
    public function __construct( $cidr = NULL )
    {
        $this->init();
        $this->set( 'cidr', $cidr );
    }

    public function __toString()
    {
        $prefix = str_replace( '/', '|', $this->attr( 'cidr' ) );
        $prefix .= '|' . $this->attr( 'maxLength' );

        return $prefix;
    }

    protected function init()
    {
        $this->define( NULL, new Element( 'cidr' ) )
            ->test( new RegExp( [ 'pattern' => '/^[0-9a-f:.]+\/\d+$/i' ] ) );

        $this->define( NULL, new Element( 'maxLength' ) )
            ->test( 'ctype_digit' );
    }

    public function isValid()
    {
        return $this->attr( 'cidr' )->isValid();
    }
}
