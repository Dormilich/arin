<?php
// Ip.php

namespace Dormilich\ARIN\Validators;

/**
 * Test a value if it qualifies as IP address.
 */
class Ip
{
    const V4 = '4';
    const V6 = '6';
    const ALL = 'all';
    // adds FILTER_FLAG_NO_PRIV_RANGE flag (skip private ranges)
    const V4_NO_PRIV = '4_no_priv';
    const V6_NO_PRIV = '6_no_priv';
    const ALL_NO_PRIV = 'all_no_priv';
    // adds FILTER_FLAG_NO_RES_RANGE flag (skip reserved ranges)
    const V4_NO_RES = '4_no_res';
    const V6_NO_RES = '6_no_res';
    const ALL_NO_RES = 'all_no_res';
    // adds FILTER_FLAG_NO_PRIV_RANGE and FILTER_FLAG_NO_RES_RANGE flags (skip both)
    const V4_ONLY_PUBLIC = '4_public';
    const V6_ONLY_PUBLIC = '6_public';
    const ALL_ONLY_PUBLIC = 'all_public';

    private $flags;

    /**
     * Create validator with the allowed values. The values may be of any type 
     * as long as they can be converted to a string.
     * 
     * @param array $setup The configuration for the validator.
     * @return self
     */
    public function __construct( array $setup )
    {
        if ( isset( $setup[ 'version' ] ) ) {
            $this->flags = $this->getFlags( $setup[ 'version' ] );
        }
    }

    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param scalar $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        return filter_var( $value, FILTER_VALIDATE_IP, $this->flags ) !== false;
    }

    /**
     * Convert the IP version string into a set of filter flags.
     * 
     * @param string $version 
     * @return integer|NULL
     */
    protected function getFlags( $version )
    {
        switch ( $version ) {
            case self::V4:
               return FILTER_FLAG_IPV4;

            case self::V6:
               return FILTER_FLAG_IPV6;

            case self::V4_NO_PRIV:
               return FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE;

            case self::V6_NO_PRIV:
               return FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE;

            case self::ALL_NO_PRIV:
               return FILTER_FLAG_NO_PRIV_RANGE;

            case self::V4_NO_RES:
               return FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE;

            case self::V6_NO_RES:
               return FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE;

            case self::ALL_NO_RES:
               return FILTER_FLAG_NO_RES_RANGE;

            case self::V4_ONLY_PUBLIC:
               return FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

            case self::V6_ONLY_PUBLIC:
               return FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

            case self::ALL_ONLY_PUBLIC:
               return FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

            default:
                return NULL;
        }
    }
}
