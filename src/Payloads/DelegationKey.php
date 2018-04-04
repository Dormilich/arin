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

        $algo = new MapTransformer( [
            'RSASHA1' => '5',
            'RSASHA1-NSEC3-SHA1' => '7',
            'RSASHA256' => '8',
            'RSASHA512' => '10',
            'ECDSAP256SHA256' => '13',
            'ECDSAP384SHA384' => '14',
        ] );

        //  5 => RSASHA1 (RFC 4034)
        //  7 => RSASHA1-NSEC3-SHA1 (RFC 5155), alias for 5
        //  8 => RSASHA256 (RFC 5702)
        // 10 => RSASHA512 (RFC 5702)
        // 13 => ECDSAP256SHA256 (RFC 6605)
        // 14 => ECDSAP384SHA384 (RFC 6605)
        $this->define( NULL, new Element( 'algorithm' ) )
            ->apply( $algo )
            ->test( new Choice( [ 'choices' => [ 5, 7, 8, 10, 13, 14 ] ] ) );
        // a hash value
        $this->define( NULL, new Element( 'digest' ) )
            ->test( 'ctype_xdigit' );
        // validity duration since submission in seconds
        $this->define( NULL, new Element( 'ttl' ) )
            ->apply( $int )
            ->test( 'ctype_digit' );
        // should be 1 for algo 5/7, 2 for algo 8
        $this->define( 'type', new Element( 'digestType' ) )
            ->apply( $int )
            ->test( new Choice( [ 'choices' => [ 1, 2, 3, 4 ] ] ) );
        // unsigned 16bit integer (RFC 4034)
        $this->define( NULL, new Element( 'keyTag' ) )
            ->apply( $int )
            ->test( 'ctype_digit' );
    }
}
