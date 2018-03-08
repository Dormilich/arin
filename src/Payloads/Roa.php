<?php
// Roa.php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\XmlSerializable;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Exceptions\ValidationException;

/**
 * The ROA Payload allows for Route Origin Authorization (ROA) request 
 * submissions.
 * 
 * Please complete the roaData field using the following format:
 * 
 * versionNumber|signingTime|name|originAS|validityStartDate|validityEndDate|startAddress|cidrLength|maxLength|
 * Ex. 1|1340135296|My First ROA|1234|05-25-2011|05-25-2012|10.0.0.0|8|16|192.168.0.0|18||
 * 
 * Note that startAddress|cidrLength|maxLength| are repeated for each resource. 
 * The versionNumber field must be set to 1, as it is currently the only 
 * supported version. The signingTime is a timestamp specifying when the ROA 
 * data was signed, specified in seconds since the unix epoch (January 1, 1970). 
 * The name field may contain any name of your choosing, and is for your own 
 * identification purposes. The originAs field is the AS that will be 
 * authorized to announce the resources present in the roaData. The 
 * validityStartDate and validityEndDate specifies the date range during which 
 * your ROA must be considered valid, and must be within the range of your 
 * resource certificate. These dates must be specified in the mm-dd-yyyy format.
 * 
 * The signature field of the RoaPayload is the signed base64 encoding of the 
 * roaData field. More information about ROA signing may be found on ARIN’s 
 * RPKI FAQ. 
 */
class Roa extends Payload implements XmlSerializable
{
    /**
     * @inheritDoc
     */
    protected $xmlns = 'http://www.arin.net/regrws/rpki/v1';

    /**
     * @inheritDoc
     */
    protected $name = 'roa';

    /**
     * @var string The resource class value in the API request.
     */
    private $resource = 'AR';

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->define( NULL, new Element( 'signature' ) )
            ->test( function ( $value ) {
                return strlen($value) and false !== base64_decode( $value, true );
            } );

        $this->define( 'data', new RoaData );
    }

    /**
     * @inheritDoc
     */
    public function xmlSerialize()
    {
        if ( ! $this->isValid() ) {
            $msg = 'Roa Payload "%s" is not valid for submission.';
            $msg = sprintf( $msg, $this->attr( 'data' )->get( 'name' ) ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( 'UTF-8' );
        return $this->xmlAppend( $root )->asXML();
    }

    /**
     * Get the boolean value for the API request’s resourceClass option. 
     * If a parameter is passed to the function, this method is used as setter.
     * 
     * The default value is "AR" (ARIN region).
     * 
     * @return boolean
     */
    public function resourceClass()
    {
        if ( func_num_args() === 1 ) {
            $this->resource = $this->getResourceClass( func_get_arg( 0 ) );
        }
        return $this->resource;
    }

    /**
     * Validate the resourceClass value.
     * 
     * @param string $value 
     * @return string
     */
    private function getResourceClass( $value )
    {
        $value = strtoupper( $value );

        $valid = [ 'AR', 'AP', 'RN', 'LN', 'AF' ];
        if ( in_array( $value, $valid, true ) ) {
            return $value;
        }

        $msg = 'Value [%s] is not a valid ROA resource class.';
        throw new ValidationException( sprintf( $msg, $value ) );
    }
}
