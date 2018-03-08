<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Payload;

/**
 * This payload is contained within a MessagePayload returned during a Get 
 * Message call, or of a MessageReference Payload when a Get Ticket Details 
 * call is performed and the msgRefs parameter is specified as 'true'. 
 * 
 * This AttachmentReference Payload should not be submitted by itself.
 */
class AttachmentReference extends Payload
{
    /**
     * @inheritDoc
     */
    protected $name = 'attachmentReference';

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->define( 'filename', new Generated( 'attachmentFilename' ) );
        $this->define( 'id', new Generated( 'attachmentId' ) );
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        return false;
    }

    /**
     * Returns the message ID.
     * 
     * @return string
     */
    public function getHandle()
    {
        return $this->get( 'id' );
    }
}
