<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Validators\ClassList;

/**
 * This payload is a nested element of a Ticket Payload returned when a Get 
 * Ticket Details call is performed and the msgRefs parameter is specified as 
 * 'true'. You can then request a Get Message call with a specified MessageID, 
 * and will be returned a MessagePayload. 
 * 
 * This MessageReference Payload should not be submitted by itself.
 */
class MessageReference extends Payload
{
    protected $name = 'messageReference';

    public function __toString()
    {
        return (string) $this->get( 'id' );
    }

    protected function init()
    {
        $this->define( 'references', new Group( 'attachmentReferences' ) )
            ->test( new ClassList( [ 'choices' => AttachmentReference::class ] ) );

        $this->define( 'id', new Generated( 'messageId' ) );
    }
}
