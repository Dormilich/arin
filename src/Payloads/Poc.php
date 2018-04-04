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
use Dormilich\ARIN\Transformers\DatetimeTransformer;
use Dormilich\ARIN\Transformers\ElementTransformer;
use Dormilich\ARIN\Validators\Choice;
use Dormilich\ARIN\Validators\ClassList;
use Dormilich\ARIN\Validators\Email;
use Dormilich\ARIN\Validators\RegExp;

/**
 * The POC Payload provides information about a POC.
 * 
 * The comment field can be used to display operational information about the 
 * Customer (NOC hours, website, etc.). All comments must be accurate and 
 * operational in nature. ARIN reserves the right to edit or remove public 
 * comments.
 * 
 * The following fields are automatically filled in once you submit the payload, and should be left blank:
 *  - handle
 *  - registrationDate
 * 
 * When performing a modify, if you include these fields with a different 
 * value from the original, omit them entirely, or leave them blank, it will 
 * return an error.
 * 
 * The following fields may not be modified:
 *  - contactType
 *  - firstName
 *  - middleName
 *  - lastName
 * 
 * If you alter, modify, or omit these fields when performing a POC Modify, 
 * you will receive an error.
 * 
 * The iso-3166-1 field refers to an international standard for country codes. 
 * More information is available at: http://en.wikipedia.org/wiki/ISO_3166-1.
 * 
 * The iso-3166-2 refers to an international standard for state, province, 
 * county, or other relevant subdivisions as defined by each country. 
 * More information is available at: http://en.wikipedia.org/wiki/ISO_3166-2
 * 
 *  - ISO-3166-1 is mandatory for all new POCs.
 *  - ISO-3166-2 is required for U.S. and Canada.
 * 
 *     Note: Each POC must have at least one Office Phone listed.
 */
class Poc extends Payload implements Primary
{
    /**
     * @inheritDoc
     */
    protected $name = 'poc';

    /**
     * @var boolean The `makeLink` parameter in the API request.
     */
    private $linked = true;

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
        $email = new Element( 'email' );
        $email->test( new Email );

        $this->define( 'state', new Element( 'iso3166-2' ) )
            ->apply( $upper )
            ->test( new RegExp( [ 'pattern' => '/^[A-Z0-9]{1,3}$/' ] ) );

        $this->define( 'country', new Country );

        $this->define( 'email', new Group( 'emails' ) )
            ->apply( new ElementTransformer( $email ) );

        $this->define( 'address', new MultiLine( 'streetAddress' ) );

        $this->define( NULL, new Element( 'city' ) );

        $this->define( 'zip', new Element( 'postalCode' ) );

        $this->define( NULL, new MultiLine( 'comment' ) );

        $this->define( 'created', new Generated( 'registrationDate' ) )
            ->apply( new DatetimeTransformer );

        $this->define( NULL, new Generated( 'handle' ) )
            ->apply( $upper );

        $this->define( 'type', new ReadOnly( 'contactType' ) )
            ->apply( $upper )
            ->test( new Choice( [ 'choices' => [ 'PERSON', 'ROLE' ] ] ) );

        $this->define( 'company', new Element( 'companyName' ) );

        $this->define( NULL, new ReadOnly( 'firstName' ) );

        $this->define( NULL, new ReadOnly( 'middleName' ) );

        $this->define( NULL, new ReadOnly( 'lastName' ) );

        $this->define( 'phone', new Group( 'phones' ) )
            ->test( new ClassList( [ 'choices' => Phone::class ] ) );
    }

    /**
     * @inheritDoc
     */
    public function getHandle()
    {
        return $this->attr( 'handle' )->jsonSerialize();
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
            $msg = 'Poc Payload %s is not valid for submission.';
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
    private function validCreate( array $valid )
    {
        $attr = [ 'type', 'country', 'address', 'city', 'email', 'phone', 'lastName' ];

        if ( $valid[ 'handle' ] or $valid[ 'created' ] ) {
            return false;
        }

        return $this->validate( $attr, $valid ) and $this->validType( $valid );
    }

    /**
     * Determine if the object is valid for an update request.
     * 
     * @param array $valid Validity matrix.
     * @return boolean
     */
    private function validUpdate( array $valid )
    {
        $attr = [ 'handle', 'created', 'type', 'country', 'address', 'city', 'email', 'phone', 'lastName' ];

        return $this->validate( $attr, $valid ) and ( $valid[ 'firstName' ] or $valid[ 'company' ] );
    }

    /**
     * Validate the name fields based on the type field.
     * 
     * @param array $valid Validity matrix.
     * @return boolean
     */
    private function validType( array $valid )
    {
        switch ( $this->get( 'type' ) ) {
            case 'PERSON':
                return $valid[ 'firstName' ];

            case 'ROLE':
                return ! $valid[ 'firstName' ] and $valid[ 'company' ];

            default:
                // can only happen if the validator is changed
                return false;
        }
    }

    /**
     * Get the value for the API request’s makeLink option. 
     * If a parameter is passed to the function, this method is used as setter.
     * 
     * The default value is TRUE (link Poc to account).
     * 
     * @return string
     */
    public function makeLink()
    {
        if ( func_num_args() === 1 ) {
            $this->linked = filter_var( func_get_arg( 0 ), FILTER_VALIDATE_BOOLEAN );
        }
        return $this->linked ? 'true' : 'false';
    }
}
