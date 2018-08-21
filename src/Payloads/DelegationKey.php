<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Transformers\IntegerTransformer;
use Dormilich\ARIN\Transformers\MapTransformer;
use Dormilich\ARIN\Transformers\StackTransformer;
use Dormilich\ARIN\Validators\Choice;

/**
 * The Delegation Key Payload is the portion of the Delegation Payload that 
 * contains algorithm name and digest type information.
 * 
 * The algorithm name and digest type name will be determined by the values 
 * you enter. You do not need to set the name on the payload. If you do, it 
 * will be discarded.
 * 
 * @see https://www.iana.org/assignments/dns-sec-alg-numbers/dns-sec-alg-numbers.xhtml
 */
class DelegationKey extends Payload
{
    /**
     * @inheritDoc
     */
    protected $name = 'delegationKey';

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $int = new IntegerTransformer;
        // IANA DNSSEC algorithm number
        $this->define( NULL, new Element( 'algorithm' ) )
            ->apply( $this->getAlgorithmMap() )
            ->test( new Choice( [ 'choices' => [ 5, 7, 8, 10, 13, 14 ] ] ) );
        // hash value
        $this->define( NULL, new Element( 'digest' ) )
            ->test( 'ctype_xdigit' );
        // validity duration since submission in seconds
        $this->define( NULL, new Element( 'ttl' ) )
            ->apply( $int )
            ->test( 'ctype_digit' );
        // IANA DS RR digest type number
        $this->define( 'type', new Element( 'digestType' ) )
            ->apply( $this->getDigestTypeMap() )
            ->test( new Choice( [ 'choices' => [ 1, 2, 3, 4 ] ] ) );
        // unsigned 16bit integer (RFC 4034)
        $this->define( NULL, new Element( 'keyTag' ) )
            ->apply( $int )
            ->test( 'ctype_digit' );
    }

    /**
     * Map of DNSSEC algorithm numbers.
     * 
     * @see https://www.iana.org/assignments/dns-sec-alg-numbers/dns-sec-alg-numbers.xhtml
     * @return MapTransformer
     */
    private function getAlgorithmMap()
    {
        $algo = array(
            'DELETE',
            'RSA/MD5',
            'Diffie-Hellman',
            'DSA/SHA-1',
            'reserved',
            'RSA/SHA-1', // 5
            'DSA-NSEC3-SHA1',
            'RSASHA1-NSEC3-SHA1', // 7
            'RSA/SHA-256', // 8
            'reserved',
            'RSA/SHA-512', // 10
            'reserved',
            'ECC-GOST',
            'ECDSA-P256/SHA-256', // 13
            'ECDSA-P384/SHA-384', // 14
            'Ed25519',
            'Ed448',
        );

        return $this->getMapTransformer($algo);
    }

    /**
     * Map of DS RR type digest algorithms.
     * 
     * @see https://www.iana.org/assignments/ds-rr-types/ds-rr-types.xhtml
     * @return MapTransformer
     */
    private function getDigestTypeMap()
    {
        $type = array(
            'reserved',
            'SHA-1',   // => 5, 7
            'SHA-256', // => 8, 13
            'GOST R 34.11-94',
            'SHA-384', // => 14
        );

        return $this->getMapTransformer($type);
    }

    /**
     * Convert the list of names into an appropriate mapper.
     * 
     * @param array $data 
     * @return MapTransformer
     */
    private function getMapTransformer(array $data)
    {
        $data = array_flip($data);
        $data = array_map('strval', $data);
        unset($data['reserved']);

        return new MapTransformer($data);
    }
}
