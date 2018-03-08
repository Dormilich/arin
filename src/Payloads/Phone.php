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
    /**
     * @inheritDoc
     */
    protected $name = 'phone';

    /**
     * @param string|null $number 
     * @return self
     */
    public function __construct( $number = NULL )
    {
        $this->init();
        $this->set( 'number', $number );
    }

    /**
     * Return the phone number without extension.
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->get( 'number' );
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->define( 'type', new PhoneType );
        $this->define( NULL, new Element( 'number' ) );
        $this->define( NULL, new Element( 'extension' ) );
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        $valid = $this->validity();

        return $valid[ 'type' ] and $valid[ 'number' ];
    }

    /**
     * @inheritDoc
     */
    public function xmlSerialize()
    {
        if ( ! $this->isValid() ) {
            $msg = 'Phone Payload %s is not valid for submission.';
            $msg = sprintf( $msg, var_export( $this->get( 'number' ), true ) ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( 'UTF-8' );
        return $this->xmlAppend( $root )->asXML();
    }
}
