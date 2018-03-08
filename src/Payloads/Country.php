<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Exceptions\ARINException;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Transformers\IntegerTransformer;
use Dormilich\ARIN\Validators\Range;
use Dormilich\ARIN\Validators\RegExp;

/**
 * The Country Payload identifies a country using two-digit, three-digit, 
 * and/or e164 codes.
 * 
 * The name and e164 (ITU-T E.164 international calling codes) fields are 
 * not required. Either the two-digit (code2) or three-digit (code3) code 
 * fields must be specified. If you specify both,they must match the same 
 * country.
 */
class Country extends Payload
{
    /**
     * @inheritDoc
     */
    protected $name = 'iso3166-1';

    /**
     * @param string|NULL $handle Alpha2 or Alpha3 country code.
     * @return self
     */
    public function __construct( $handle = NULL )
    {
        $this->init();

        try {
            $this->set( 'code2', $handle );
        } catch ( ARINException $e ) {
            $this->set( 'code3', $handle );
        }
    }

    /**
     * Returns the country abbreviation.
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->get( 'code2' ) ?: $this->get( 'code3' );
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $upper = new CallbackTransformer( 'strtoupper' );

        $this->define( NULL, new Element( 'name' ) );

        $this->define( NULL, new Element( 'code2' ) )
            ->apply( $upper )
            ->test( new RegExp( [ 'pattern' => '/^[A-Z]{2}$/' ] ) );

        $this->define( NULL, new Element( 'code3' ) )
            ->apply( $upper )
            ->test( new RegExp( [ 'pattern' => '/^[A-Z]{3}$/' ] ) );

        $this->define( NULL, new Element( 'e164' ) )
            ->apply( new IntegerTransformer )
            ->test( new Range( [ 'min' => 1, 'max' => 999 ] ) );
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        $valid = $this->validity();
        return $valid[ 'code2' ] or $valid[ 'code3' ];
    }
}
