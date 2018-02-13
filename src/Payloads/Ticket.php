<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\XmlSerializable;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Transformers\BooleanTransformer;
use Dormilich\ARIN\Validators\ClassList;

/**
 * The Ticket Payload contains details about a submitted Ticket. Some calls 
 * using this payload will be automatically processed. Others may require 
 * manual intervention by ARIN staff, in which case this payload will provide 
 * details regarding your request.
 * 
 * The following fields are automatically completed by Reg-RWS, and should be 
 * left blank:
 *  - ticketNo
 *  - createdDate
 *  - resolvedDate
 *  - closedDate
 *  - updatedDate
 *  - webTicketType
 *  - webTicketResolution
 * 
 * If you alter, modify, or omit these fields when performing a Ticket Modify, 
 * you will receive an error.
 * 
 * --
 * 
 * A ticket is not marked a primary object since it will use its own cURL client.
 */
class Ticket extends Payload implements XmlSerializable
{
    protected $name = 'ticket';

    private $msgRefs = true;

    public function __construct( $number = NULL )
    {
        $this->init();
        $this->set( 'ticketNo', $number );
    }

    protected function init()
    {
        $uri = 'http://www.arin.net/regrws/shared-ticket/v1';

        $this->define( NULL, new Group( 'messages' ) )
            ->test( new ClassList( [ 'choices' => Message::class ] ) );

        $this->define( 'references', new Group( 'messageReferences' ) )
            ->test( new ClassList( [ 'choices' => MessageReference::class ] ) );

        $this->define( NULL, new Generated( 'ticketNo' ) );

        $this->define( NULL, new Element( 'ns4:shared', $uri ) )
            ->apply( new BooleanTransformer );

        $this->define( 'org', new Element( 'ns4:orgHandle', $uri ) );

        $this->define( 'created', new Generated( 'createdDate' ) );

        $this->define( 'resolved', new Generated( 'resolvedDate' ) );

        $this->define( 'closed', new Generated( 'closedDate' ) );

        $this->define( 'updated', new Generated( 'updatedDate' ) );

        $this->define( 'type', new Generated( 'webTicketType' ) );

        $this->define( 'status', new Element( 'webTicketStatus' ) );

        $this->define( 'resolution', new Generated( 'webTicketResolution' ) );
    }

    /**
     * Tickets are pretty much read-only but other requests need its PK.
     */
    public function getHandle()
    {
        return $this->get( 'ticketNo' );
    }

    public function isValid()
    {
        $valid = $this->find( 'ticketNo', 'created', 'type', 'status', 'resolution' );

        return array_reduce( $valid, function ( $carry, XmlHandlerInterface $item ) {
            return $carry and $item->isValid();
        }, true );
    }

    private function modifyValid()
    {
        $modify = $this->find( 'ticketNo', 'resolved', 'type', 'resolution' );

        return array_reduce( $modify, function ( $carry, XmlHandlerInterface $item ) {
            return $carry and $item->isValid();
        }, $this->get( 'status' ) === 'CLOSED' );
    }

    public function xmlSerialize( $encoding = 'UTF-8' )
    {
        if ( ! $this->modifyValid() ) {
            $msg = 'Ticket Payload "%s" is not valid for submission.';
            $msg = sprintf( $msg, $this->getHandle() ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( $encoding );
        return $this->xmlAppend( $root );
    }

    public function msgRefs()
    {
        if ( func_num_args() === 1 ) {
            $this->msgRefs = filter_var( func_get_arg( 0 ), FILTER_VALIDATE_BOOLEAN );
        }
        return $this->msgRefs;
    }
}

/* 
Web Ticket Resolution:

ACCEPTED, DENIED, ABANDONED, ANSWERED, PROCESSED, DUPLICATE, UNSUCCESSFUL, OTHER

Web Ticket Status: 

PENDING_CONFIRMATION, PENDING_REVIEW, ASSIGNED, IN_PROGRESS, WAIT_LIST, RESOLVED, 
CLOSED, APPROVED, ANY, ANY_OPEN

Web Ticket Type:

QUESTION, ASSOCIATIONS_REPORT, REASSIGNMENT_REPORT, WHOWAS_REPORT, WHOWAS_ACCESS, 
ORG_CREATE, EDIT_ORG_NAME, ORG_RECOVERY, TRANSFER_PREAPPROVAL, TRANSFER_RECIPIENT_82, 
TRANSFER_RECIPIENT_83, TRANSFER_SOURCE_83, TRANSFER_LISTING_SERVICE, IPV4_SIMPLE_REASSIGN, 
IPV4_DETAILED_REASSIGN, IPV4_REALLOCATE, IPV6_DETAILED_REASSIGN, IPV6_REALLOCATE, 
NET_DELETE_REQUEST, ISP_IPV4_REQUEST, ISP_IPV6_REQUEST, CREATE_RESOURCE_CERTIFICATE, 
CREATE_ROA, END_USER_IPV4_REQUEST, END_USER_IPV6_REQUEST, ASN_REQUEST, EDIT_BILLING_CONTACT_INFO, ANY
*/
