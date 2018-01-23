<?php
// MultiLine.php

namespace Dormilich\ARIN\Elements;

use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\Exceptions\ValidationException;
use Dormilich\ARIN\Traits;

/**
 * An element representing a multi-line text block.
 * 
 * With regards to the API this is a Payload, but logically (and thus programmatically) 
 * this is an Element (although the common ground is so low that inheriting from 
 * Element is not effective).
 */
class MultiLine implements XmlHandlerInterface, \ArrayAccess, \Countable, \Iterator
{
    /**
     * @var string[] $value The lines inside the text block.
     */
    protected $values = [];

    use Traits\Attributes
      , Traits\NamespaceSetup
    ;

    /**
     * Setting up the basic XML definition. The name may be either a tag name
     * —or if a namespace is given—a qualified name.
     * 
     * @param string $name Tag name.
     * @param string $ns Namespace URI.
     * @return self
     * @throws LogicException Invalid namespace URI.
     * @throws LogicException Namespace prefix missing.
     */
    public function __construct( $name, $ns = NULL )
    {
        $this->setNamespace( (string) $name, $ns );
    }

    /**
     * Return the original text block of the element.
     * 
     * @return string
     */
    public function __toString()
    {
        return implode( PHP_EOL, $this->values );
    }

    /**
     * Return the text block as text lines.
     * 
     * @return string[]
     */
    public function getValue()
    {
        return $this->values;
    }

    /**
     * Set the content of the element.
     * 
     * @param string|string[] $value New element text content.
     * @return self
     */
    public function setValue( $value )
    {
        $this->values = [];

        $this->addValue( $value );

        return $this;
    }

    /**
     * Add a line or text block item to the collection.
     * 
     * @param string $value 
     * @return self
     */
    public function addValue( $value )
    {
        if ( NULL === $value ) {
            return $this;
        }

        foreach ( $this->loop( $value ) as $v ) {
            $this->values[] = $this->convert( $v );
        }

        return $this;
    }

    /**
     * Returns TRUE if the element’s data collection is not empty.
     * 
     * @return boolean
     */
    public function isValid()
    {
        return count( $this->values ) > 0;
    }

    /**
     * Check if there are members in the collection. 
     * 
     * @return boolean
     */
    public function isDefined()
    {
        return count( $this->values ) > 0;
    }

    /**
     * Convert input into an iterable structure.
     *
     * @param mixed $value
     * @return array|Traversable
     */
    protected function loop( $value )
    {
        if ( $value instanceof \Traversable ) {
            return $value;
        }
        // split block text into lines
        if ( is_string( $value ) and strpos( $value, "\n" ) !== false ) {
            $value = str_replace( "\r\n", "\n", $value );
            $value = explode( "\n", $value );
        }

        if ( ! is_array( $value ) ) {
            $value = [ $value ];
        }

        return $value;
    }

    /**
     * Convert the data item into a string.
     * 
     * @param mixed $value 
     * @return string
     * @throws ValidationException Value not stringifiable.
     */
    protected function convert( $value )
    {
        $value = $this->transform( $value );

        if ( is_string( $value ) ) {
            return $value;
        }

        $msg = 'Value [%s] is not allowed for the multi-line [%s] element.';
        $type = is_object( $value ) ? get_class( $value ) : gettype( $value );
        $data = is_scalar( $value ) ? var_export( $value, true ) : $type;
        throw new ValidationException( sprintf( $msg, $data, $this->getName() ) );
    }

    /**
     * Transforms a value to a string.
     * 
     * @param mixed $value 
     * @return mixed
     */
    protected function transform( $value )
    {
        if ( is_bool( $value ) ) {
            // booleans just don't make sense in this context
        }
        elseif ( is_scalar( $value ) ) {
            $value = (string) $value;
        }
        elseif ( is_object( $value ) and method_exists( $value, '__toString' ) ) {
            $value = (string) $value;
        }

        return $value;
    }

    /**
     * Convert the element object into an XML node.
     * 
     * @param SimpleXMLElement $node The parent XML node to append the element to.
     * @return SimpleXMLElement
     */
    public function xmlAppend( \SimpleXMLElement $node )
    {
        if ( ! $this->isValid() ) {
            return $node;
        }

        $ns = $this->getNamespace();
        $elem = $node->addChild( $this->getTag(), null, $ns );

        $index = 1;
        foreach ( $this->values as $value ) {
            $elem->addChild( 'line', $value, $ns )->addAttribute( 'number', $index++ );
        }

        return $node;
    }

    /**
     * Convert an XML node into an object.
     * 
     * @param SimpleXMLElement $node The XML node to parse.
     * @return void
     */
    public function xmlParse( \SimpleXMLElement $node )
    {
        foreach ( $node->children() as $line ) {
            $this->addValue( (string) $line );
        }
    }

    /**
     * Convert a multi-line value into a line object to be consistent with the 
     * array access in Payload objects.
     * 
     * @param string|null $value 
     * @param integer|null $index Array index of the value.
     * @return Element
     */
    private function toElement( $value, $index )
    {
        $e = new Element( 'line' );

        if ( NULL === $index ) {
            $e->setValue( $value );
        }
        else {
            $xml = sprintf( '<line number="%d">%s</line>', ++$index, $value );
            $e->xmlParse( new \SimpleXMLElement( $xml ) );
        }

        return $e;
    }

    /**
     * Number of lines assigned.
     *
     * @see http://php.net/Countable
     * @return integer
     */
    public function count()
    {
        return count( $this->values );
    }

    /**
     * @see http://php.net/Iterator
     * @return void
     */
    public function rewind()
    {
        reset( $this->values );
    }

    /**
     * @see http://php.net/Iterator
     * @return string
     */
    public function current()
    {
        $value = current( $this->values );
        $key = key( $this->values );

        return $this->toElement( $value, $key );
    }

    /**
     * @see http://php.net/Iterator
     * @return integer
     */
    public function key()
    {
        return key( $this->values );
    }

    /**
     * @see http://php.net/Iterator
     * @return void
     */
    public function next()
    {
        next( $this->values );
    }

    /**
     * @see http://php.net/Iterator
     * @return boolean
     */
    public function valid()
    {
        return NULL !== key( $this->values );
    }

    /**
     * Check if the requested index exists.
     * 
     * @see http://php.net/ArrayAccess
     * @param mixed $offset The array key.
     * @return boolean
     */
    public function offsetExists( $offset )
    {
        return isset( $this->values[ $offset ] );
    }

    /**
     * Get the requested text line from the collection. Allows reverse indexing 
     * (-1 being the last value, etc.).
     * 
     * @see http://php.net/ArrayAccess
     * @param mixed $offset 
     * @return Element
     */
    public function offsetGet( $offset )
    {
        $offset = $this->fromReverseOffset( $offset );

        if ( $this->offsetExists( $offset ) ) {
            $value = $this->values[ $offset ];
        }
        else {
            $msg = 'Undefined index: '.$offset;
            trigger_error( $msg, E_USER_WARNING );
            $value = $offset = NULL;
        }

        return $this->toElement( $value, $offset );
    }

    /**
     * Lines may not be modified separately although they can be appended. If 
     * the text block needs modification, update the text block as a whole.
     * 
     * @see http://php.net/ArrayAccess
     * @param mixed $offset 
     * @param mixed $value 
     * @return void
     * @throws RuntimeException
     */
    public function offsetSet( $offset, $value )
    {
        if ( null !== $offset ) {
            $msg = 'A line inside a text block may not be modified.';
            throw new \RuntimeException( $msg );
        }

        $this->addValue( $value );
    }

    /**
     * Lines may not be removed separately. Consider modifying the text block as 
     * a whole.
     * 
     * @see http://php.net/ArrayAccess
     * @param mixed $offset 
     * @return void
     * @throws RuntimeException
     */
    public function offsetUnset( $offset )
    {
        $offset = $this->fromReverseOffset( $offset );

        if ( $this->offsetExists( $offset ) ) {
            $msg = 'A line inside a text block may not be deleted.';
            throw new \RuntimeException( $msg );
        }
    }

    /**
     * Convert negative offsets into positive ones.
     * 
     * @param integer|string $offset 
     * @return integer|string
     */
    private function fromReverseOffset( $offset )
    {
        if ( is_int( $offset ) and $offset < 0 ) {
            $offset += count( $this->values );
        }

        return $offset;
    }
}
