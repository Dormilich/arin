<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Primary;
use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\Elements\GroupInterface;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\MultiLine;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Elements\ReadOnly;
use Dormilich\ARIN\Validators\Choice;
use Dormilich\ARIN\Transformers\IntegerTransformer;
use Dormilich\ARIN\Transformers\HandleTransformer;
use Dormilich\ARIN\Validators\ClassList;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Transformers\DatetimeTransformer;
use Dormilich\ARIN\Transformers\ElementTransformer;
use Dormilich\ARIN\Validators\NamedElement;
use Dormilich\Http\NetworkInterface;

/**
 * The NET Payload contains details about an IPv4 or IPv6 network.
 * 
 * If you send a NET Payload and need to fill in the netBlock field, only 
 * either the endAddress or the cidrLength fields are required; not both. 
 * Reg-RWS will calculate the other for you, and the details for both will be 
 * returned in any call resulting in a NET Payload.
 * 
 * If you specify a NET type, it must be one of the valid codes located in the 
 * table under NET Block Payload. If you do not provide a type, Reg-RWS will 
 * determine it for you, depending on which call you are using. The version 
 * field may contain a value of "4" or "6," depending on the type of NET you 
 * are referring to. If left blank, this field will be completed for you based 
 * on the startAddress.
 * 
 * When submitting a NET Payload, the IP addresses provided in the 
 * startAddress and endAddress fields can be non-zero-padded (i.e. 10.0.0.255) 
 * or zero-padded (i.e. 010.000.000.255). The payload returned will always 
 * express IP addresses as zero-padded.
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
 * If you alter or omit these fields when performing a NET Modify, you will 
 * receive an error.
 * 
 * The orgHandle and customerHandle elements are mutually exclusive. Depending 
 * on the type of the call this payload is being used for, you are required to 
 * assign either a Customer or an ORG. One of the two values will be present 
 * at all times.
 * 
 * The following fields may not be modified during a NET Modify:
 *  - version
 *  - orgHandle
 *  - netBlock
 *  - customerHandle
 *  - parentNetHandle
 * 
 * If you alter or omit these fields when performing a NET Modify, you will 
 * receive an error.
 * 
 * For information on the pocLinks field, see the POC Link Payload.
 */
class Net extends Payload implements Primary
{
    /**
     * @inheritDoc
     */
    protected $name = 'net';

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
        $htf = new HandleTransformer;
        $nb = $this->netBlockTransformer();
        $as = new Element( 'originAS' );

        $this->define( NULL, new ReadOnly( 'version' ) )
            ->apply( new IntegerTransformer )
            ->test( new Choice( [ 'choices' => [ 4, 6 ] ] ) );

        $this->define( NULL, new MultiLine( 'comment' ) );

        $this->define( 'created', new Generated( 'registrationDate' ) )
            ->apply( new DatetimeTransformer );

        $this->define( 'org', new ReadOnly( 'orgHandle' ) )
            ->apply( $htf );

        $this->define( NULL, new Generated( 'handle' ) )
            ->apply( new CallbackTransformer( 'strtoupper' ) );

        $this->define( 'net', new Group( 'netBlocks' ))
            ->apply( $nb )
            ->test( new ClassList( [ 'choices' => NetBlock::class ] ) );

        $this->define( 'customer', new ReadOnly( 'customerHandle' ) )
            ->apply( $htf );

        $this->define( 'parentNet', new ReadOnly( 'parentNetHandle' ) )
            ->apply( $htf );

        $this->define( 'name', new Element( 'netName' ) );

        $this->define( 'asn', new Group( 'originASes' ) )
            ->apply( new ElementTransformer( $as ) )
            ->test( new NamedElement( [ 'name' => 'originAS' ] ) );

        $this->define( 'poc', new Group( 'pocLinks' ) )
            ->test( new ClassList( [ 'choices' => PocLinkRef::class ] ) );
    }

    /**
     * Set up the transformer for NetBlocks.
     * 
     * @return CallbackTransformer
     */
    private function netBlockTransformer()
    {
        return new CallbackTransformer( function ( $value ) {
            if ( $value instanceof NetworkInterface ) {
                $value = new NetBlock( $value );
                $value->set( 'type', 'S' );
            }
            return $value;
        } );
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
            $msg = 'Net Payload %s is not valid for submission.';
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
        $list[] = ! $valid[ 'handle' ];
        $list[] = ! $valid[ 'created' ];
        $list[] = $valid[ 'net' ];
        $list[] = $valid[ 'name' ];
        $list[] = $valid[ 'parentNet' ];

        return array_reduce( $list, function ( $bool, $test ) {
            return $bool and $test;
        }, $valid[ 'customer' ] xor $valid[ 'org' ] );
    }

    /**
     * Determine if the object is valid for an update request.
     * 
     * @param array $valid Validity matrix.
     * @return boolean
     */
    private function validUpdate( array $valid )
    {
        $attr = [ 'version', 'created', 'handle', 'net', 'parentNet', 'name' ];

        $pass = $this->validate( $attr, $valid );

        return $pass and ( $valid[ 'customer' ] xor $valid[ 'org' ] );
    }
}
