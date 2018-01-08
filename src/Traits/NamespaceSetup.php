<?php
// NamespaceSetup.php

namespace Dormilich\ARIN\Traits;

trait NamespaceSetup
{
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
}
