<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Exceptions\NotFoundException;
use Dormilich\ARIN\Validators\ClassList;

/**
 * The Ticketed Request Payload details about a Ticket and or NET affected by 
 * that Ticket. If the call you are using may result in a NET being returned 
 * or a Ticket being returned, a Ticketed Request Payload is returned. This 
 * may occur when performing a reallocation/reassignment. If your reallocation 
 * or reassignment meets the conditions for automatic processing, the Ticketed 
 * Request Payload will have an embedded NET Payload representing the NET that 
 * was created. If your reallocation or reassignment does not meet the 
 * conditions for automatic processing, the Ticket Request Payload will have 
 * an embedded Ticket Payload representing the Ticket that was created for 
 * your request. See NET Reassign and NET Reallocate for more details.
 */
class TicketedRequest extends Payload
{
    protected $name = 'ticketedRequest';

    protected function init()
    {
        $this->define( 'net', new Net );
        $this->define( 'ticket', new Ticket );
    }

    /**
     * Only allow access to the defined payload.
     * 
     * @param string $name 
     * @return boolean
     */
    public function has( $name )
    {
        $elements = $this->children();
        return isset( $elements[ $name ] ) and (bool) $elements[ $name ]->getHandle();
    }

    /**
     * Get a matching and valid child object.
     * 
     * @param string $name 
     * @return Payload
     */
    public function attr( $name )
    {
        if ( $this->has( $name ) ) {
            $elements = $this->children();
            return $elements[ $name ];
        }

        $msg = 'Element "%s" not found in the TicketedRequest Payload.';
        throw new NotFoundException( sprintf( $msg, $name ) );
    }

    /**
     * Disable setting undefined payload.
     * 
     * @param string $name 
     * @param mixed $value 
     * @return void
     * @throws BadMethodCallException
     */
    public function set( $name, $value )
    {
        $msg = 'The TicketedRequest Payload is read-only.';
        throw new \BadMethodCallException( $msg );
    }

    /**
     * Disable setting undefined payload.
     * 
     * @param string $name 
     * @param mixed $value 
     * @return void
     * @throws BadMethodCallException
     */
    public function add( $name, $value )
    {
        $msg = 'The TicketedRequest Payload is read-only.';
        throw new \BadMethodCallException( $msg );
    }
}
