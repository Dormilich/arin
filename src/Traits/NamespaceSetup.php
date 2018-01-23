<?php
// NamespaceSetup.php

namespace Dormilich\ARIN\Traits;

trait NamespaceSetup
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
     * Set namespace and prefix.
     * 
     * @param string $tag Prefixed tag name.
     * @param string $namespace Namespace URI.
     * @return void
     * @throws LogicException Namespace prefix missing.
     */
    protected function setNamespace( $tag, $namespace )
    {
        if ( filter_var( $namespace, FILTER_VALIDATE_URL ) ) {
            if ( strpos( $tag, ':' ) ) {
                list( $this->prefix, $this->name ) = explode( ':', $tag, 2 );
            }
            else {
                $this->name = $tag;
            }
            $this->namespace = $namespace;
        }
        else {
            $names = explode( ':', $tag, 2 );
            $this->name = end( $names );
        }
    }

    /**
     * Get the element’s tag name (local name).
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the element’s tag name (local name).
     * 
     * @return string
     */
    public function getTag()
    {
        return ltrim( $this->prefix . ':' . $this->name, ':' );
    }

    /**
     * Get the element’s tag name (local name).
     * 
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }
}
