<?php
// Payload.php

namespace Dormilich\ARIN\Elements;

use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\Exceptions\ARINException;
use Dormilich\ARIN\Exceptions\NotFoundException;
use Dormilich\ARIN\Exceptions\ParserException;
use Dormilich\ARIN\Exceptions\ValidationException;

/**
 * The base class of almost all payload objects.
 */
abstract class Payload implements XmlHandlerInterface, \ArrayAccess, \Iterator, \JsonSerializable
{
    /**
     * @var string REG-RWS XML namespace.
     */
    protected $xmlns = 'http://www.arin.net/regrws/core/v1';

    /**
     * @var string Name of the Payload’s XML base element.
     */
    protected $name;

    /**
     * @var XMLHandlerInterface[] Child elements of the Payload.
     */
    private $elements = [];

    /**
     * Initialise payload.
     * 
     * @return self
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Set up the definition of the Payload’s child elements.
     * 
     * @return void
     */
    abstract protected function init();

    /**
     * Helper method for adding a child object.
     * 
     * @param string|null $alias 
     * @param XmlHandlerInterface $elem 
     * @return XmlHandlerInterface
     * @throws LogicException Duplicate alias.
     */
    protected function define( $alias, XmlHandlerInterface $elem )
    {
        if ( ! $alias ) {
            $alias = $elem->getName();
        }

        if ( array_key_exists( $alias, $this->elements ) ) {
            throw new \LogicException( 'Duplicate attribute alias '.$alias );
        }

        return $this->elements[ $alias ] = $elem;
    }

    /**
     * Reset the payload’s elements on cloning.
     * 
     * @return void
     */
    public function __clone()
    {
        $this->elements = array_map( function ( XmlHandlerInterface $elem ) {
            return clone $elem;
        }, $this->elements );
    }

    /**
     * Get the name of the Payload’s base XML element.
     * 
     * @return string Base XML element’s tag name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the namespace of the Payload’s base XML element.
     * 
     * @return string Base XML element’s namespace.
     */
    public function getNamespace()
    {
        return $this->xmlns;
    }

    /**
     * Returns TRUE if all elements are defined. This should be overwritten by 
     * the individual payloads according to the API specification.
     * 
     * @return boolean
     */
    public function isValid()
    {
        return array_reduce( $this->elements, function ( $carry, XmlHandlerInterface $elem ) {
            return $carry and $elem->isValid();
        }, true );
    }

    /**
     * Fallback if a payload’s value is accessed as if it were an element. 
     * 
     * @return array
     */
    public function getValue()
    {
        return array_map( function ( $elem ) {
            return $elem->getValue();
        }, $this->elements );
    }

    /**
     * Access the payload element property in derived classes.
     * 
     * @param boolean $onlyValid
     * @return XMLHandlerInterface[]
     */
    protected function children( $onlyValid = false )
    {
        if ( ! $onlyValid ) {
            return $this->elements;
        }

        return array_filter( $this->elements, function ( XmlHandlerInterface $elem ) {
            return $elem->isValid();
        } );
    }

    /**
     * Get all elements whose tag name matches the given value(s).
     * 
     * @param mixed $name Tag name.
     * @return array List of matching elements.
     */
    protected function find( $name )
    {
        $list = func_get_args();
        return array_filter( $this->elements, function ( XmlHandlerInterface $item, $alias ) use ( $list ) {
            return in_array( $alias, $list, true ) or in_array( $item->getName(), $list, true );
        }, ARRAY_FILTER_USE_BOTH );
    }

    /**
     * Get the first element whose alias or tag name matches the given value 
     * case-insensitively.
     * 
     * @param mixed $name Tag name or alias.
     * @return object|NULL First matching element or NULL if no matching 
     *          element was found.
     */
    protected function fetch( $name )
    {
        $elements = array_filter( $this->elements, function ( XmlHandlerInterface $elem, $alias ) use ( $name ) {
            return strcasecmp( $name, $alias ) === 0 or strcasecmp( $name, $elem->getName() ) === 0;
        }, ARRAY_FILTER_USE_BOTH);

        return reset( $elements ) ?: NULL;
    }

    /**
     * Do a case-insensitive match of the given name against the aliases and tag names.
     * 
     * @param string $name Tag name or alias.
     * @return boolean
     */
    public function has( $name )
    {
        // isset is faster than a filter
        return isset( $this->elements[ $name ] ) or (bool) $this->fetch( $name );
    }

    /**
     * Get a matching child object.
     * 
     * @param string $name Tag name or alias.
     * @return Element|Group|MultiLine|Payload
     */
    public function attr( $name )
    {
        // case-sensitive alias
        if ( isset( $this->elements[ $name ] ) ) {
            return $this->elements[ $name ];
        }
        // case-insensitive match
        if ( $elem = $this->fetch( $name ) ) {
            return $elem;
        }

        $msg = 'Element "%s" not found in the %s Payload.';
        throw new NotFoundException( sprintf( $msg, $name, ucfirst( $this->getName() ) ) );
    }

    /**
     * Get the value of a child object.
     * 
     * @param string $name Tag name or alias.
     * @return string|string[]|array
     */
    public function get( $name )
    {
        return $this->attr( $name )->getValue();
    }

    /**
     * Set the value of a child object.
     * 
     * @param string $name Tag name or alias.
     * @param mixed $value Input value.
     * @return self
     * @throws 
     */
    public function set( $name, $value )
    {
        $elem = $this->attr( $name );
        $key = array_search( $elem, $this->elements, true );
        // element or group
        if ( $elem instanceof ElementInterface ) {
            $elem->setValue( $value );
        }
        // payload
        elseif ( $value instanceof $elem ) {
            $this->elements[ $key ] = $value;
        }
        elseif ( NULL === $value ) {
            $class = get_class( $elem );
            $this->elements[ $key ] = new $class;
        }
        else {
            $msg = 'Value [%s] cannot overwrite a %s Payload.';
            $type = is_object( $value ) ? get_class( $value ) : gettype( $value );
            $data = is_scalar( $value ) ? var_export( $value, true ) : $type;
            throw new \UnexpectedValueException( sprintf( $msg, $data, ucfirst( $this->getName() ) ) );
        }

        return $this;
    }

    /**
     * Add a value to a group element or an empty element.
     * 
     * @param string $name Tag name or alias.
     * @param mixed $value Input value.
     * @return self
     * @throws 
     */
    public function add( $name, $value )
    {
        $elem = $this->attr( $name );
        // add to a group
        if ( $elem instanceof GroupInterface ) {
            $elem->addValue( $value );
        }
        // add to an empty element
        elseif ( $elem instanceof ElementInterface and ! $elem->isValid() ) {
            $elem->setValue( $value );
        }
        else {
            $msg = 'Cannot add a value to a non-group element (%s).';
            throw new \UnexpectedValueException( sprintf( $msg, $name ) );
        }

        return $this;
    }

    /**
     * Helper function to create the root XML element.
     * 
     * @param string $encoding The XML character encoding.
     * @return SimpleXMLElement
     */
    protected function xmlCreate( $encoding )
    {
        $xml  = sprintf( '<?xml version="1.0" encoding="%s"?>', $encoding );
        $xml .= sprintf( '<%s xmlns="%s"/>', $this->getName(), $this->getNamespace() );

        return simplexml_load_string( $xml );
    }

    /**
     * Convert the payload object into an XML node.
     * 
     * @param SimpleXMLElement $node The parent XML node to append the payload to.
     * @return SimpleXMLElement
     */
    public function xmlAppend( \SimpleXMLElement $node )
    {
        if ( ! $this->isValid() ) {
            return $node;
        }

        $elem = $node;
        // do not append to the root node, if this is the root object
        if ( $node->getName() !== $this->getName() ) {
            $elem = $node->addChild( $this->getName(), NULL, $this->getNamespace() );
        }

        foreach ( $this->elements as $child ) {
            $child->xmlAppend( $elem );
        }

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
        $ns = $node->getNamespaces( true ) ?: [ NULL ];

        foreach ( $ns as $prefix => $namespace ) {
            foreach ( $node->children( $namespace ) as $name => $child ) {
                $this->fetch( $name )->xmlParse( $child );
            }
        }
    }

    /**
     * Parse an XML string into the respective payload object. If no suitable 
     * payload class is found, put it into a group. 
     * 
     * @param string $xmlString 
     * @return Payload
     * @throws ErrorException XML reading error.
     */
    public static function fromXML( $xmlString )
    {
        set_error_handler( function ( $code, $msg, $file, $line ) {
            restore_error_handler();
            throw new \ErrorException( $msg, 0, $code, $file, $line );
        } );
        $xml = simplexml_load_string( $xmlString );
        restore_error_handler();

        $payload = self::getPayloadClass( $xml );
        $payload->xmlParse( $xml );

        return $payload;
    }

    /**
     * Determine the payload class from the XML element.
     * 
     * @param SimpleXMLElement $root 
     * @return Payload
     * @throws ParserException Invalid XML root element.
     */
    private static function getPayloadClass( \SimpleXMLElement $root )
    {
        $name = ucfirst( $root->getName() );
        $class = 'Dormilich\\ARIN\\Payloads\\' . $name;

        if ( class_exists( $class ) ) {
            return new $class;
        }

        $msg = sprintf( 'Payload %s is not known.', $name );
        throw new ParserException( $msg );
    }

    /**
     * @see http://php.net/JsonSerializable
     */
    public function jsonSerialize()
    {
        return $this->children( true );
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
     * Get the current element’s name (not its alias).
     * 
     * @see http://php.net/Iterator
     * @return string
     */
    public function key()
    {
        return current( $this->elements )->getName();
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
     * Check if a named or aliased element exists.
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Element name or alias.
     * @return boolean
     */
    public function offsetExists( $offset )
    {
        return $this->has( $offset );
    }

    /**
     * Get a named or aliased element via array access.
     * Accessing nested elements via array access is worth more than getting a 
     * child element’s value directly.
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Element name or alias.
     * @return ElementInterface
     * @throws NotFoundException
     */
    public function offsetGet( $offset )
    {
        return $this->attr( $offset );
    }

    /**
     * @see http://php.net/ArrayAccess
     * @param string $offset Element name or alias.
     * @param mixed $value 
     * @return void
     * @throws ValidationException
     */
    public function offsetSet( $offset, $value )
    {
        $this->set( $offset, $value );
    }

    /**
     * Unset the content of a named or aliased element.
     * 
     * @see http://php.net/ArrayAccess
     * 
     * @param string $offset Element name or alias.
     * @return void
     * @throws ValidationException E.g. trying to unset a read-only element.
     */
    public function offsetUnset( $offset )
    {
        if ( $this->has( $offset ) ) {
            $this->set( $offset, NULL );
        }
    }
}
