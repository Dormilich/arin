<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\XmlSerializable;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;

/**
 * The Phone Payload is used by the POC Payload and as a standalone structure 
 * by the Add Phone call.
 * 
 * The number field should be in NANP format if applicable. The extension 
 * field is optional and can be left blank or not included in the payload you 
 * submit.
 */
class Phone extends Payload implements XmlSerializable
{
    protected $name = 'phone';

    public function __construct( $number = NULL )
    {
        $this->init();
        $this->set( 'number', $number );
    }

    public function __toString()
    {
        return (string) $this->get( 'number' );
    }

    protected function init()
    {
        $this->define( 'type', new PhoneType );
        $this->define( NULL, new Element( 'number' ) );
        $this->define( NULL, new Element( 'extension' ) );
    }

    public function isValid()
    {
        return  $this->attr( 'type' )->isValid()
            and $this->attr( 'number' )->isValid();
    }

    public function xmlSerialize( $encoding = 'UTF-8' )
    {
        if ( ! $this->isValid() ) {
            $msg = 'Phone Payload %s is not valid for submission.';
            $msg = sprintf( $msg, var_export( $this->get( 'number' ), true ) ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( $encoding );
        return $this->xmlAppend( $root );
    }
}
