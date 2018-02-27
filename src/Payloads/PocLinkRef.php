<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Exceptions\ARINException;
use Dormilich\ARIN\Exceptions\ParserException;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Validators\Choice;

/**
 * This payload is a nested object within ORG and NET Payloads, explaining the 
 * POC Handle(s) associated with that object and the function it is serving.
 * 
 * The description field will be completed automatically based on the 
 * information provided in the function field, and should be left blank.
 * 
 *     Note:Admin ("AD") POCs may not be added to NETs. 
 */
class PocLinkRef extends Payload
{
    protected $name = 'pocLinkRef';

    public function __construct( $handle = NULL )
    {
        $this->init();
        $this->set( 'handle', $handle );
    }

    protected function init()
    {
        $this->define( NULL, new Element( 'description' ) );

        $this->define( NULL, new Element( 'handle' ) );

        $this->define( NULL, new Element( 'function' ) )
            ->apply( new CallbackTransformer( 'strtoupper' ) )
            ->test( new Choice( [ 'choices' => [ 'AD', 'AB', 'N', 'T' ] ] ) );
    }

    public function isValid()
    {
        $valid = $this->validity();

        return $valid[ 'function' ] and $valid[ 'handle' ];
    }

    /**
     * Convert the PocLinkRef object into an XML node.
     * 
     * @param SimpleXMLElement $node The parent XML node to append the payload to.
     * @return SimpleXMLElement
     */
    public function xmlAppend( \SimpleXMLElement $node )
    {
        $elem = $node->addChild( $this->getName(), NULL, $this->getNamespace() );

        foreach ( $this->children( true ) as $name => $child ) {
            $elem->addAttribute( $name, (string) $child );
        }

        return $node;
    }

    /**
     * Convert an XML node into a PocLinkRef object.
     * 
     * @param SimpleXMLElement $node The XML node to parse.
     * @return void
     * @throws ParserException Unable to read some XML data.
     */
    public function xmlParse( \SimpleXMLElement $node )
    {
        foreach ( $node->attributes() as $name => $value ) {
            $this->set( $name, $value );
        }
    }
}
