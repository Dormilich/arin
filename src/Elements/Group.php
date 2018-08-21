<?php
// Group.php

namespace Dormilich\ARIN\Elements;

use Dormilich\ARIN\Primary;
use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Exceptions\ParserException;
use Dormilich\ARIN\Exceptions\ValidationException;
use Dormilich\ARIN\Traits;
use Dormilich\ARIN\Transformers\StackTransformer;

/**
 * A group represents a list of Elements or Payloads.
 */
class Group implements GroupInterface, XmlHandlerInterface, Transformable, Validatable, \ArrayAccess, \Countable, \Iterator, \JsonSerializable
{
    /**
     * @var XmlHandlerInterface[] $elements 
     */
    private $elements = [];

    use Traits\DataTransformation
      , Traits\DataValidation
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

        $this->transformer = $this->getDefaultTransformer();
        $this->validator   = $this->getDefaultValidator();
    }

    /**
     * Define the default transformer class. This transformer must be replaced 
     * in the implementation.
     * 
     * @return DataTransformerInterface
     */
    protected function getDefaultTransformer()
    {
        // an empty transformer
        return new StackTransformer;
    }

    /**
     * Define the default validator callback. The validator should only check 
     * the validity of the object type, as the objects can be invalidated after 
     * adding them to the collection. A validator should always be defined to 
     * aid XML deserialisation.
     * 
     * @see Group::createObjectFrom()
     * @return callable
     */
    protected function getDefaultValidator()
    {
        return 'is_object';
    }

    /**
     * Get the transformed text content of the collection.
     * 
     * @return array|string[]
     */
    public function getValue()
    {
        return array_map( function ( $e ) {
            return $e->getValue();
        }, $this->elements );
    }

    /**
     * Set the content of the collection.
     * 
     * @param mixed $value New collection element(s).
     * @return self
     */
    public function setValue( $value )
    {
        $this->elements = [];

        $this->addValue( $value );

        return $this;
    }

    /**
     * Add content to the collection.
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
            $this->elements[] = $this->convert( $v );
        }

        return $this;
    }

    /**
     * Check if any member of the collection is valid.
     * 
     * @return boolean
     */
    public function isValid()
    {
        return array_reduce( $this->elements, function ( $bool, XmlHandlerInterface $item ) {
            return $bool or $item->isValid();
        }, false );
    }

    /**
     * Convert input into an iterable structure.
     *
     * @param mixed $value
     * @return array|Traversable
     */
    protected function loop( $value )
    {
        if ( is_array( $value ) ) {
            return $value;
        }
        // payloads are traversable but must be used as is
        if ( $value instanceof Payload ) {
            return [ $value ];
        }
        // groups fall in this category
        if ( $value instanceof \Traversable ) {
            return $value;
        }

        return [ $value ];
    }

    /**
     * Convert the data item into a string.
     * 
     * Note:
     *  This does not check if the value itself is valid as objects can be 
     *  invalidated after they are added to the group.
     * 
     * @param mixed $value 
     * @return string
     * @throws ValidationException Value not stringifiable.
     */
    protected function convert( $value )
    {
        $value = $this->transform( $value );

        if ( $value instanceof XmlHandlerInterface and $this->validate( $value ) ) {
            return $value;
        }

        $msg = 'Value [%s] is not allowed for the [%s] group element.';
        $type = is_object( $value ) ? get_class( $value ) : gettype( $value );
        $data = is_scalar( $value ) ? var_export( $value, true ) : $type;
        throw new ValidationException( sprintf( $msg, $data, $this->getName() ) );
    }

    /**
     * Convert the group object into an XML node.
     * 
     * @param SimpleXMLElement $node The parent XML node to append the group to.
     * @return SimpleXMLElement
     */
    public function xmlAppend( \SimpleXMLElement $node )
    {
        if ( ! $this->isValid() ) {
            return $node;
        }

        $elem = $node->addChild( $this->getTag(), NULL, $this->getNamespace() );

        foreach ( $this->elements as $child ) {
            $child->xmlAppend( $elem );
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
        foreach ( $node->children() as $name => $child ) {
            $elem = $this->createObjectFrom( $child );
            // there is no guarantee that the transformer created the desired object
            if ( ! $elem instanceof XmlHandlerInterface ) {
                $msg = 'XML element <%s> is not valid for the [%s] group element.';
                throw new ParserException( sprintf( $msg, $name, $this->getName() ) );
            }

            $elem->xmlParse( $child );
            $this->addValue( $elem );
        }
    }

    /**
     * Create an ARIN object from a SimpleXMLElement, if possible. Simple Element 
     * objects are created directly by the transformer, Payloads are initialised 
     * from the XML element name.
     * 
     * @param SimpleXMLElement $child XML element.
     * @return XmlHandlerInterface|SimpleXMLElement
     */
    private function createObjectFrom( \SimpleXMLElement $child )
    {
        $elem = NULL;

        $payload = 'Dormilich\\ARIN\\Payloads\\' . ucfirst( $child->getName() );
        if ( class_exists( $payload ) ) {
            $elem = new $payload;
        }
        // there are cases where a simple element has the same name as a payload
        // e.g. message in the error payload
        if ( ! $this->validate( $elem ) ) {
            $elem = $this->transformer->transform( $child );
        }

        return $elem;
    }

    /**
     * Test if an array key exists.
     * 
     * @param mixed $offset 
     * @return boolean
     */
    private function arrayKeyExists( $offset )
    {
        return isset( $this->elements[ $offset ] );
    }

    /**
     * Convert non-list indices into list indices. This includes negative offsets 
     * and handle-to-index mapping.
     * 
     * @param integer|string $offset 
     * @return integer|string
     */
    private function convertOffset( $offset )
    {
        $offset = $this->fromReverseOffset( $offset );
        $offset = $this->fromHandle( $offset );

        return $offset;
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
            $offset += count( $this->elements );
        }

        return $offset;
    }

    /**
     * Convert members' handles into the corresponding offset.
     * 
     * @param integer|string $offset 
     * @return integer|string
     */
    private function fromHandle( $offset )
    {
        if ( is_int( $offset ) and $offset < count( $this->elements ) ) {
            return $offset;
        }

        $primary = array_filter( $this->elements, function ( $element ) {
            // some objects have handles, but are not primary objects
            return method_exists( $element, 'getHandle' );
        } );

        $handles = array_map( function ( $element ) {
            return $element->getHandle();
        }, $primary );

        $key = array_search( (string) $offset, $handles, true );

        return $key === false ? $offset : $key;
    }

    /**
     * Number of objects assigned.
     *
     * @see http://php.net/Countable
     * @return integer
     */
    public function count()
    {
        return count( $this->elements );
    }

    /**
     * @see http://php.net/Iterator
     * @return void
     */
    public function rewind()
    {
        reset( $this->elements );
    }

    /**
     * @see http://php.net/Iterator
     * @return XmlHandlerInterface
     */
    public function current()
    {
        return current( $this->elements );
    }

    /**
     * @see http://php.net/Iterator
     * @return integer
     */
    public function key()
    {
        return key( $this->elements );
    }

    /**
     * @see http://php.net/Iterator
     * @return void
     */
    public function next()
    {
        next( $this->elements );
    }

    /**
     * @see http://php.net/Iterator
     * @return boolean
     */
    public function valid()
    {
        return NULL !== key( $this->elements );
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
        $offset = $this->convertOffset( $offset );

        return $this->arrayKeyExists( $offset );
    }

    /**
     * Get the requested text line from the collection. Returns NULL if index 
     * does not exist. Allows reverse indexing (-1 being the last value, etc.).
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset 
     * @return Element
     */
    public function offsetGet( $offset )
    {
        $offset = $this->convertOffset( $offset );

        if ( $this->arrayKeyExists( $offset ) ) {
            return $this->elements[ $offset ];
        }

        $msg = 'Undefined index: '.$offset;
        trigger_error( $msg, E_USER_WARNING );

        return $this->transformer->transform( NULL );
    }

    /**
     * Set a text line at the requested index. If the index is not found in the 
     * collection, the value is appended instead. Text blocks are resolved 
     * adding more than one line. Allows reverse indexing.
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset 
     * @param mixed $value 
     * @return void
     */
    public function offsetSet( $offset, $value )
    {
        $offset = $this->convertOffset( $offset );

        if ( $this->arrayKeyExists( $offset ) ) {
            array_splice( $this->elements, $offset, 0, [ $this->convert( $value ) ] );
        }
        elseif ( NULL === $offset ) {
            $this->addValue( $value );
        }
    }

    /**
     * Remove text line at the requested position. The collection will be 
     * re-indexed after the removal. Allows reverse indexing.
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset 
     * @return void
     */
    public function offsetUnset( $offset )
    {
        $offset = $this->convertOffset( $offset );

        if ( $this->arrayKeyExists( $offset ) ) {
            array_splice( $this->elements, $offset, 1 );
        }
    }

    /**
     * @see http://php.net/JsonSerializable
     */
    public function jsonSerialize()
    {
        return $this->elements;
    }
}
