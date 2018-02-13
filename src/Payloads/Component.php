<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Payload;

/**
 * Component Error Payloads represent individual component errors in the Error Payload. 
 */
class Component extends Payload
{
    protected $name = 'component';

    protected function init()
    {
        $this->define( NULL, new Generated( 'name' ) );
        $this->define( NULL, new Generated( 'message' ) );
    }
}
