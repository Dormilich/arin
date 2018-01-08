<?php
// IpTransformer.php

namespace Dormilich\ARIN\Transformers;

/**
 * Convert IP values.
 */
class IpTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform( $value )
    {
        return $this->unpad( (string) $value );
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform( $value )
    {
        if ( $value ) {
            // IPv4
            $value = $this->unpad( $value );
            // IPv6
            $value = inet_ntop( inet_pton( $value ) );
        }

        return $value;
    }

    /**
     * Unpad any IPv4 address so it can be run through filter_var().
     * Handling of padded IPv4 addresses is seemingly dependent on the 
     * underlying OS.
     * 
     * @param string $value IPv4 candidate string.
     * @return string Unpadded IPv4 or original string.
     */
    protected function unpad( $value )
    {
        if ( preg_match( '/^\d+(\.\d+){3}$/', $value ) ) {
            $value = implode( '.', sscanf( $value, '%d.%d.%d.%d' ) );
        }

        return $value;
    }
}
