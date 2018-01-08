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
class Element implements ElementInterface, XmlHandlerInterface
{
    /**
     * @var string $name XML tag name.
     */
    protected $name;

    /**
     * @var string $prefix XML namespace prefix.
     */
    protected $prefix;

    /**
     * @var string $namespace XML namespace URI.
     */
    protected $namespace;

    /**
     * @var string $value The textContent of the element.
     */
    protected $value;

    /**
     * @var array $attributes XML attibute definitions.
     */
    protected $attributes = [];

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
     * Getter for a generated attribute.
     * 
     * Note: RegRWS only uses element values, element attributes, and
     *       element values with generated attributes (DelegationKey).
     * 
     * @param string $name XML attribute name.
     * @return string|NULL Attribute value.
     */
    public function __get( $name )
    {
        if ( isset( $this->attributes[ $name ] ) ) {
            return $this->attributes[ $name ];
        }
        return NULL;
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
        return 'is_scalar';
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
     * Returns TRUE if the element’s value is set.
     * 
     * @return boolean
     */
    public function isDefined()
    {
        return $this->value !== NULL;
    }

    /**
     * Convert the data item into a string.
     * 
     * @param mixed $value 
     * @return string
     * @throws DataTypeException Value not stringifiable.
     */
    protected function convert( $value )
    {
        $value = $this->transform( $value );
        // an element’s content must always be a scalar value
        // no matter what custom validator is set
        if ( is_scalar( $value ) and $this->validate( $value ) ) {
            return is_bool( $value ) ? var_export( $value, true ) : (string) $value;
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

        $name = ltrim( $this->prefix . ':' . $this->name, ':' );
        $elem = $node->addChild( $name, $this->value, $this->namespace );

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
}
