<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Primary;
use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\MultiLine;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Elements\ReadOnly;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Validators\ClassList;
use Dormilich\ARIN\Validators\RegExp;

/**
 * The ORG Payload provides details about an organization, including their 
 * address and contact information.
 * 
 * The main difference between the ORG Payload and Customer Payload is the 
 * privateCustomer field, which an ORG Payload does not contain.
 * 
 * The comment field can be used to display operational information about the 
 * Customer (NOC hours, website, etc.). All comments must be accurate and 
 * operational in nature. ARIN reserves the right to edit or remove public 
 * comments.
 * 
 * The following fields are automatically filled in once you submit the 
 * payload, and should be left blank:
 *  - handle
 *  - registrationDate
 * 
 * The following fields may not be modified:
 *  - orgName
 *  - dbaName
 * 
 * If you alter, modify, or omit these fields when performing a ORG Modify, 
 * you will receive an error.
 * 
 * The element name orgURL is meant for a Referral Whois (RWhois) server 
 * hostname and port, not for the URL of the company's website. RWhois is a 
 * protocol typically run on port 4321 and is described in RFC 2167.
 * 
 * For information on the pocLinks field, see the POC Link Payload.
 * 
 * The iso-3166-1 field refers to an international standard for country codes. 
 * More information is available at: http://en.wikipedia.org/wiki/ISO_3166-1.
 * 
 * The iso-3166-2 refers to an international standard for state, province, 
 * county, or other relevant subdivisions as defined by each country. 
 * More information is available at: http://en.wikipedia.org/wiki/ISO_3166-2
 * 
 *  - ISO-3166-1 is mandatory for all ORGs.
 *  - ISO-3166-2 is required for U.S. and Canada.
 */
class Org extends Payload implements Primary
{
    protected $name = 'org';

    public function __construct( $handle = NULL )
    {
        $this->init();
        $this->set( 'handle', $handle );
    }

    public function __toString()
    {
        return (string) $this->getHandle();
    }

    protected function init()
    {
        $upper = new CallbackTransformer( 'strtoupper' );

        $this->define( 'country', new Country );

        $this->define( 'address', new MultiLine( 'streetAddress' ) );

        $this->define( NULL, new Element( 'city' ) );

        $this->define( 'state', new Element( 'iso3166-2' ) )
            ->apply( $upper )
            ->test( new RegExp( [ 'pattern' => '/^[A-Z0-9]{1,3}$/' ] ) );

        $this->define( 'zip', new Element( 'postalCode' ) );

        $this->define( NULL, new MultiLine( 'comment' ) );

        $this->define( 'created', new Generated( 'registrationDate' ) );

        $this->define( NULL, new Generated( 'handle' ) )
            ->apply( $upper );

        $this->define( 'name', new ReadOnly( 'orgName' ) );

        $this->define( NULL, new ReadOnly( 'dbaName' ) );

        $this->define( NULL, new Element( 'taxId' ) );

        $this->define( NULL, new Element( 'orgUrl' ) );

        $this->define( 'poc', new Group( 'pocLinks' ) )
            ->test( new ClassList( [ 'choices' => PocLinkRef::class ] ) );
    }

    public function getHandle()
    {
        return $this->attr( 'handle' )->jsonSerialize();
    }

    public function isValid()
    {
        $elements = $this->find( 'address', 'orgName', 'country', 'city', 'poc' ); 

        return array_reduce( $elements, function ( $carry, XmlHandlerInterface $item ) {
            return $carry and $item->isValid();
        }, true );
    }

    public function xmlSerialize( $encoding = 'UTF-8' )
    {
        if ( ! $this->isValid() ) {
            $msg = 'Org Payload %s is not valid for submission.';
            $msg = sprintf( $msg, var_export( $this->getHandle(), true ) ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( $encoding );
        return $this->xmlAppend( $root );
    }
}
