<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\ElementInterface;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Exceptions\ARINException;
use Dormilich\ARIN\Exceptions\ParserException;
use Dormilich\ARIN\Exceptions\ValidationException;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Transformers\DatetimeTransformer;
use Dormilich\ARIN\Transformers\IntegerTransformer;
use Dormilich\ARIN\Transformers\StringTransformer;
use Dormilich\ARIN\Validators\Choice;
use Dormilich\ARIN\Validators\ClassList;
use Dormilich\ARIN\Validators\Datetime;
use Dormilich\ARIN\Validators\RegExp;

/**
 * This is a meta object for use in the Roa payload. The XML value is an unwieldy 
 * string that is hard to compose by hand.
 */
class RoaData extends Payload implements ElementInterface
{
    /**
     * @inheritDoc
     */
    protected $xmlns = 'http://www.arin.net/regrws/rpki/v1';

    /**
     * @inheritDoc
     */
    protected $name = 'roaData';

    /**
     * Return the RoaData string.
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $asn = $this->asnTransformer();
        $dateTf = new DatetimeTransformer( NULL, 'm-d-Y' );
        $dateVd = new Datetime( [ 'format' => 'm-d-Y' ] );
        // @see https://www.arin.net/resources/rpki/roarequest.html#signing
        $this->define( NULL, new Element( 'version' ) )
            ->apply( new IntegerTransformer )
            ->test( new Choice( [ 'choices' => 1 ] ) )
            ->setValue( 1 );

        $this->define( NULL, new Element( 'signed' ) )
            ->apply( new DatetimeTransformer( NULL, 'U' ) )
            ->test( new Datetime( [ 'format' => 'U' ] ) );

        $this->define( NULL, new Element( 'name' ) );

        $this->define( 'asn', new Element( 'originAS' ) )
            ->apply( $asn )
            ->test( 'ctype_digit' );
        // allow some leeway due to timezone differences
        $this->define( 'start', new Element( 'validityStart' ) )
            ->apply( $dateTf )
            ->test( $dateVd );
        // allow some leeway due to timezone differences
        $this->define( 'end', new Element( 'validityEnd' ) )
            ->apply( $dateTf )
            ->test( $dateVd );

        $this->define( 'prefix', new Group( 'roaPrefixes' ) )
            ->test( new ClassList( [ 'choices' => RoaPrefix::class ] ) );
    }

    /**
     * Create the data transformer for the ASN part.
     * 
     * @return CallbackTransformer
     */
    private function asnTransformer()
    {
        return new CallbackTransformer( function ( $value ) {
            return preg_replace( '/^AS(\d+)$/', '$1', $value );
        },  function ( $value ) {
            return sprintf( 'AS%d', $value );
        } );
    }

    /**
     * Convert the payload object into an XML node.
     * 
     * @param SimpleXMLElement $node The parent XML node to append the payload to.
     * @return SimpleXMLElement
     */
    public function xmlAppend( \SimpleXMLElement $node )
    {
        $this->transform()->xmlAppend( $node );

        return $node;
    }

    /**
     * Convert an XML node into a payload object.
     * 
     * @param SimpleXMLElement $node The XML node to parse.
     * @return void
     * @throws ParserException Unable to read some XML data.
     */
    public function xmlParse( \SimpleXMLElement $node )
    {
        $this->reverseTransform( (string) $node );
    }

    /**
     * Return the roaData string.
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->transform()->getValue();
    }

    /**
     * Read a roaData string.
     * 
     * @param string $value 
     * @return self
     */
    public function setValue( $value )
    {
        $this->removeValues();

        if ( NULL !== $value ) {
            $this->convert( $value );
        }

        return $this;
    }

    /**
     * Convert the Payload into an Element as per API specification.
     * 
     * @return Element
     */
    private function transform()
    {
        $prefix = $this->attr( 'prefix' );

        $data[] = $this->attr( 'version' );
        $data[] = $this->attr( 'signed' );
        $data[] = $this->attr( 'name' );
        $data[] = $this->attr( 'asn' );
        $data[] = $this->attr( 'start' );
        $data[] = $this->attr( 'end' );
        $data = array_merge( $data, iterator_to_array( $prefix ) );

        $text = array_reduce( $data, function ( $str, $item ) {
            return $str . $item . '|';
        }, '' );

        $roaData = new Element( 'roaData' );
        $roaData->setValue( $text );

        return $roaData;
    }

    /**
     * Convert the XML string into the payload data.
     * 
     * @param string $value 
     * @return void
     */
    private function reverseTransform( $value )
    {
        $text = trim( $value, "| \t\n\r\0" );
        $data = explode( '|', $text );

        $this->set( 'version', array_shift( $data ) );
        $this->set( 'signed', array_shift( $data ) );
        $this->set( 'name', array_shift( $data ) );
        $this->set( 'asn', array_shift( $data ) );
        $this->set( 'start', array_shift( $data ) );
        $this->set( 'end', array_shift( $data ) );

        $prefix = $this->attr( 'prefix' );

        while ( $data ) {
            $cidr = array_shift( $data ) . '/' . array_shift( $data );
            $max = array_shift( $data ) ?: NULL;
            $block = new RoaPrefix( $cidr );
            $block->set( 'maxLength', $max );
            $prefix->addValue( $block );
        }
    }

    /**
     * Unset all payload elements.
     * 
     * @return void
     */
    private function removeValues()
    {
        $elements = $this->children();
        array_walk( $elements, function ( ElementInterface $elem ) {
            $elem->setValue( NULL );
        } );
    }

    /**
     * Convert the input into the payload data.
     * 
     * @param mixed $value 
     * @return void
     * @throws ValidationException Input is not a string.
     */
    private function convert( $value )
    {
        $tf = new StringTransformer;
        $value = $tf->transform( $value );

        if ( is_string( $value ) ) {
            return $this->reverseTransform( $value );
        }

        $msg = 'Value [%s] is not allowed for the [roaData] element.';
        $type = is_object( $value ) ? get_class( $value ) : gettype( $value );
        throw new ValidationException( sprintf( $msg, $type ) );
    }
}
