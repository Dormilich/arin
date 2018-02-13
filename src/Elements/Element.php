<?php
// Element.php

namespace Dormilich\ARIN\Elements;

use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\Exceptions\ValidationException;
use Dormilich\ARIN\Traits;
use Dormilich\ARIN\Transformers\DataTransformerInterface;
use Dormilich\ARIN\Transformers\StringTransformer;

/**
 * An Element represents a single XML tag without nested XML tags.
 */
class Element implements ElementInterface, XmlHandlerInterface, Transformable, Validatable, \JsonSerializable
{
    /**
     * @var string $value The textContent of the element.
     */
    protected $value;

    use Traits\Attributes
      , Traits\DataTransformation
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
     * Return the element’s text content.
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * Define the default transformer class.
     * 
     * @return DataTransformerInterface
     */
    protected function getDefaultTransformer()
    {
        return new StringTransformer;
    }

    /**
     * Define the default validator callback.
     * 
     * @return callable
     */
    protected function getDefaultValidator()
    {
        return 'is_string';
    }

    /**
     * Get the transformed text content of the element.
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->transformer->reverseTransform( $this->value );
    }

    /**
     * Set the text content of the element.
     * 
     * @param string $value New element text content.
     * @return self
     */
    public function setValue( $value )
    {
        $this->value = NULL;

        if ( NULL !== $value ) {
            $this->value = $this->convert( $value );
        }

        return $this;
    }

    /**
     * Returns TRUE if the element’s text content passes the validator.
     * 
     * @return boolean
     */
    public function isValid()
    {
        return $this->validate( $this->value );
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

        if ( is_string( $value ) and $this->validate( $value ) ) {
            return $value;
        }

        $msg = 'Value [%s] is not allowed for the [%s] element.';
        $type = is_object( $value ) ? get_class( $value ) : gettype( $value );
        $data = is_scalar( $value ) ? var_export( $value, true ) : $type;
        throw new ValidationException( sprintf( $msg, $data, $this->getName() ) );
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

        $elem = $node->addChild( $this->getTag(), $this->value, $this->getNamespace() );

        foreach ( $this->attributes as $name => $value ) {
            $elem->addAttribute( $name, $value );
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
        $this->setValue( (string) $node );

        foreach ( $node->attributes() as $name => $value ) {
            $this->attributes[ $name ] = (string) $value;
        }
    }

    /**
     * @see http://php.net/JsonSerializable
     */
    public function jsonSerialize()
    {
        return $this->value;
    }
}
