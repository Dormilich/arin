<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\XmlSerializable;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\MultiLine;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Transformers\DatetimeTransformer;
use Dormilich\ARIN\Validators\Choice;
use Dormilich\ARIN\Validators\ClassList;

/**
 * This payload allows the sending of additional information to an existing 
 * Ticket and to enable users to get a specific message and any accompanying 
 * attachment(s). The body of the payload will vary depending on the action 
 * requested.
 * 
 * The following fields are automatically completed by Reg-RWS, and should be 
 * left blank:
 *  - messageId
 *  - createdDate
 */
class Message extends Payload implements XmlSerializable
{
    /**
     * @inheritDoc
     */
    protected $name = 'message';

    /**
     * Returns the message text.
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->attr( 'text' );
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $xmlns = 'http://www.arin.net/regrws/messages/v1';

        $this->define( 'id', new Generated( 'ns2:messageId', $xmlns ) ); // integer

        $this->define( 'created', new Generated( 'ns2:createdDate', $xmlns ) )
            ->apply( new DatetimeTransformer );
        // while the subject is optional, it makes replies easier to track
        // when viewing them in the online account
        $this->define( NULL, new Element( 'subject' ) );

        $this->define( NULL, new MultiLine( 'text' ) );

        $this->define( NULL, new Element( 'category' ) )
            ->test( new Choice( [ 'choices' => [ 'NONE', 'JUSTIFICATION' ] ] ) )
            ->setValue( 'NONE' );

        $this->define( NULL, new Group( 'attachments' ) )
            ->apply( new CallbackTransformer( function ( $value ) {
                return $value instanceof Attachment ? $value : new Attachment( $value );
            } ) )
            ->test( new ClassList( [ 'choices' => Attachment::class ] ) );
        // response only
        $this->define( 'references', new Group( 'attachmentReferences' ) )
            ->test( new ClassList( [ 'choices' => AttachmentReference::class ] ) );
    }

    /**
     * The ID of the message.
     * 
     * @return string|NULL
     */
    public function getHandle()
    {
        return $this->get( 'id' );
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        $valid = $this->validity();

        return ! $valid[ 'id' ] and ! $valid[ 'references' ] and ( $valid[ 'text' ] or $valid[ 'attachments' ] );
    }

    /**
     * @inheritDoc
     */
    public function xmlSerialize()
    {
        if ( ! $this->isValid() ) {
            $msg = 'Message Payload "%s" is not valid for submission.';
            $msg = sprintf( $msg, $this->get( 'subject' ) ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( 'UTF-8' );
        return $this->xmlAppend( $root )->asXML();
    }
}
