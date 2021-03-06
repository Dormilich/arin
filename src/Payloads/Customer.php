<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Primary;
use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\MultiLine;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Transformers\BooleanTransformer;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Transformers\DatetimeTransformer;
use Dormilich\ARIN\Transformers\HandleTransformer;
use Dormilich\ARIN\Validators\RegExp;

/**
 * The Customer Payload contains details about a Customer.
 * 
 * The main difference between the ORG Payload and Customer Payload is the 
 * privateCustomer field. If true, the name and address fields will not be 
 * publicly visible when the ORG is displayed. If false or not provided, the 
 * Customer will be visible as if it were an ORG. Additionally, the Customer 
 * Payload does not have a dbaName, taxId,or  orgUrl field, nor does it have 
 * any related POCs.
 * 
 * The comment field can be used to display operational information about the 
 * Customer (NOC hours, website, etc.). All comments must be accurate and 
 * operational in nature. ARIN reserves the right to edit or remove public 
 * comments.
 * 
 * The parentOrgHandle field must contain the handle of the ORG from which 
 * this Customer has been reallocated/reassigned resources.
 * 
 * The following fields are automatically filled in once you submit the 
 * payload, and should be left blank:
 *  - handle
 *  - registrationDate
 * 
 * When performing a modify, if you include these fields with a different 
 * value from the original, omit them entirely, or leave them blank, it will 
 * return an error.
 */
class Customer extends Payload implements Primary
{
    /**
     * @inheritDoc
     */
    protected $name = 'customer';

    /**
     * @param string|NULL $handle 
     * @return self
     */
    public function __construct( $handle = NULL )
    {
        $this->init();
        $this->set( 'handle', $handle );
    }

    /**
     * Return the primary key.
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getHandle();
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $upper = new CallbackTransformer( 'strtoupper' );

        $this->define( 'name', new Element( 'customerName' ) );

        $this->define( 'country', new Country );

        $this->define( NULL, new Generated( 'handle' ) )
            ->apply( $upper );

        $this->define( 'address', new MultiLine( 'streetAddress' ) );

        $this->define( NULL, new Element( 'city' ) );

        $this->define( 'state', new Element( 'iso3166-2' ) )
            ->apply( $upper )
            ->test( new RegExp( [ 'pattern' => '/^[A-Z0-9]{1,3}$/' ] ) );

        $this->define( 'zip', new Element( 'postalCode' ) );

        $this->define( NULL, new MultiLine( 'comment' ) );

        $this->define( 'org', new Element( 'parentOrgHandle' ) )
            ->apply( new HandleTransformer );

        $this->define( 'created', new Generated( 'registrationDate' ) )
            ->apply( new DatetimeTransformer );

        $this->define( 'private', new Element( 'privateCustomer' ) )
            ->apply( new BooleanTransformer );
    }

    /**
     * @inheritDoc
     */
    public function getHandle()
    {
        return $this->get( 'handle' );
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        $valid = $this->validity();
        return $valid[ 'handle' ] 
            ? $this->validUpdate( $valid )
            : $this->validCreate( $valid )
        ;
    }

    /**
     * @inheritDoc
     */
    public function xmlSerialize()
    {
        if ( ! $this->isValid() ) {
            $msg = 'Customer Payload %s is not valid for submission.';
            $msg = sprintf( $msg, var_export( $this->getHandle(), true ) ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( 'UTF-8' );
        return $this->xmlAppend( $root )->asXML();
    }

    /**
     * Determine if the object is valid for a create request.
     * 
     * @param array $valid Validity matrix.
     * @return boolean
     */
    protected function validCreate( array $valid )
    {
        $attr = [ 'org', 'name', 'address', 'city', 'country' ];

        if ( $valid[ 'handle' ] or $valid[ 'created' ] ) {
            return false;
        }

        return $this->validate( $attr, $valid );
    }

    /**
     * Determine if the object is valid for an update request.
     * 
     * @param array $valid Validity matrix.
     * @return boolean
     */
    protected function validUpdate( array $valid )
    {
        $attr = [ 'handle', 'org', 'created', 'name', 'address', 'city', 'country' ];

        return $this->validate( $attr, $valid );
    }
}
