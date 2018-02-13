<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Transformers\IntegerTransformer;
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
    protected $name = 'delegationKey';

    protected function init()
    {
        // 5 => RSA/SHA-1 (RFC 4034)
        // 7 => RSASHA1-NSEC3-SHA1 (RFC 5155), alias for 5
        // 8 => RSA/SHA-256 (RFC 5702)
        $this->define( NULL, new Element( 'algorithm' ) )
            ->test( new Choice( [ 'choices' => [ 5, 7, 8 ] ] ) );
        // a hash value
        $this->define( NULL, new Element( 'digest' ) )
            ->test( 'ctype_xdigit' );
        // validity duration since submission in seconds
        $this->define( NULL, new Element( 'ttl' ) )
            ->apply( new IntegerTransformer )
            ->test( 'ctype_digit' );
        // SHA-1 (RFC 4034) & SHA-256 (?)
        // should be 1 for algo 5/7, 2 for algo 8
        $this->define( 'type', new Element( 'digestType' ) )
            ->test( new Choice( [ 'choices' => [ 1, 2 ] ] ) );
        // unsigned integer (RFC 4034)
        $this->define( NULL, new Element( 'keyTag' ) )
            ->test( 'ctype_digit' );
    }
}
