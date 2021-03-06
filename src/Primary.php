<?php
// Primary.php

namespace Dormilich\ARIN;

/**
 * Denotes a primary payload that 
 *  1) can be used for a REST request
 *  2) contains a primary lookup key (handle)
 *  3) is subject to (at least one of) the CRUD operations
 * 
 * Applicable payloads are: 
 *      Customer, Delegation, Net, Org, Poc, and Ticket 
 */
interface Primary extends XmlSerializable
{
    /**
     * Get the primary (lookup) key of the payload. If the key is not defined 
     * NULL is returned (as is the default value of an empty element).
     * 
     * @return string
     */
    public function getHandle();
}
