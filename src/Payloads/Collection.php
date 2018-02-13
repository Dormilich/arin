<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Validators\ClassList;

/**
 * This payload is used as a container, to store multiple payloads and return 
 * them back to the customer. This list payload will act as a wrapper for 
 * ticket searching, the setting of phones on POCs, etc. 
 */
class Collection extends Group
{
    public function __construct()
    {
        parent::__construct( 'collection', 'http://www.arin.net/regrws/core/v1' );
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultValidator()
    {
        return function ( $value ) {
            return $value instanceof Payload;
        };
    }
}
