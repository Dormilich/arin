<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Validators\Choice;

/**
 * This represents a phone type. It is a nested element of Phone Payload and 
 * should not be submitted by itself. The description field will be 
 * automatically filled in using the information in the code field, and should 
 * be left blank. 
 */
class PhoneType extends Payload
{
    protected $name = 'type';

    protected function init()
    {
        $this->define( NULL, new Element( 'description' ) );

        $this->define( NULL, new Element( 'code' ) )
            ->apply( new CallbackTransformer( 'strtoupper' ) )
            ->test( new Choice( [ 'choices' => [ 'O', 'F', 'M' ] ] ) );
    }

    public function isValid()
    {
        return $this->attr( 'code' )->isValid();
    }
}
