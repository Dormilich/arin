<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\XmlSerializable;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\MultiLine;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Transformers\CallbackTransformer;
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
    protected $name = 'message';

    public function __construct( $subject = NULL )
    {
        $this->init();
        $this->set( 'subject', $subject );
    }

    public function __toString()
    {
        return (string) $this->attr( 'text' );
    }

    protected function init()
    {
        $xmlns = 'http://www.arin.net/regrws/messages/v1';

        $this->define( 'id', new Generated( 'ns2:messageId', $xmlns ) );

        $this->define( 'created', new Generated( 'ns2:createdDate', $xmlns ) );

        $this->define( NULL, new Element( 'subject' ) );

        $this->define( NULL, new MultiLine( 'text' ) );

        $this->define( NULL, new Element( 'category' ) )
            ->test( new Choice( [ 'choices' => [ 'NONE', 'JUSTIFICATION' ] ] ) )
            ->setValue( 'NONE' );
        // request only
        $this->define( NULL, new Group( 'attachments' ) )
            ->apply( new CallbackTransformer( function ( $value ) {
                return $value instanceof Attachment ? $value : new Attachment( $value );
            } ) )
            ->test( new ClassList( [ 'choices' => Attachment::class ] ) );
        // response only
        $this->define( 'references', new Group( 'attachmentReferences' ) )
            ->test( new ClassList( [ 'choices' => AttachmentReference::class ] ) );
    }

    public function isValid()
    {
        // request
        $subj = $this->attr( 'subject' )->isValid();
        $text = $this->attr( 'text' )->isValid();
        $cat  = $this->attr( 'category' )->isValid();
        $att  = $this->attr( 'attachments' )->isValid();

        return ( $subj and $cat and ( $text or $att ) );
    }

    public function xmlSerialize( $encoding = 'UTF-8' )
    {
        if ( ! $this->isValid() ) {
            $msg = 'Message Payload "%s" is not valid for submission.';
            $msg = sprintf( $msg, $this->get( 'subject' ) ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( $encoding );
        return $this->xmlAppend( $root );
    }
}
