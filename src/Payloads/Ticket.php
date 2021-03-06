<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\XmlSerializable;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Transformers\BooleanTransformer;
use Dormilich\ARIN\Transformers\DatetimeTransformer;
use Dormilich\ARIN\Transformers\HandleTransformer;
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
    /**
     * @inheritDoc
     */
    protected $name = 'ticket';

    /**
     * @var boolean The `msgRefs` parameter in the API request.
     */
    private $msgRefs = true;

    /**
     * @param string|NULL $number 
     * @return self
     */
    public function __construct( $number = NULL )
    {
        $this->init();
        $this->set( 'ticketNo', $number );
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $uri = 'http://www.arin.net/regrws/shared-ticket/v1';

        $date = new DatetimeTransformer;

        $this->define( NULL, new Group( 'messages' ) )
            ->test( new ClassList( [ 'choices' => Message::class ] ) );

        $this->define( 'references', new Group( 'messageReferences' ) )
            ->test( new ClassList( [ 'choices' => MessageReference::class ] ) );
        // (?<date>\d{6})-[A-Z]\d+
        $this->define( NULL, new Generated( 'ticketNo' ) );

        $this->define( NULL, new Element( 'ns4:shared', $uri ) )
            ->apply( new BooleanTransformer );

        $this->define( 'org', new Element( 'ns4:orgHandle', $uri ) )
            ->apply( new HandleTransformer );

        $this->define( 'created', new Generated( 'createdDate' ) )
            ->apply( $date );

        $this->define( 'resolved', new Generated( 'resolvedDate' ) )
            ->apply( $date );

        $this->define( 'closed', new Generated( 'closedDate' ) )
            ->apply( $date );

        $this->define( 'updated', new Generated( 'updatedDate' ) )
            ->apply( $date );

        $this->define( 'type', new Generated( 'webTicketType' ) );

        $this->define( 'status', new Element( 'webTicketStatus' ) );

        $this->define( 'resolution', new Generated( 'webTicketResolution' ) );
    }

    /**
     * Tickets are pretty much read-only but other requests need its PK.
     * 
     * @return string
     */
    public function getHandle()
    {
        return $this->get( 'ticketNo' );
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        $attr = [ 'ticketNo', 'resolved', 'type', 'resolution' ];
        $valid = $this->validity();
        $resolved = $this->validate( $attr, $valid ) and ! $valid[ 'closed' ];

        return $resolved and $this->get( 'status' ) === 'CLOSED';
    }

    /**
     * @inheritDoc
     */
    public function xmlSerialize()
    {
        if ( ! $this->isValid() ) {
            $msg = 'Ticket Payload "%s" is not valid for submission.';
            $msg = sprintf( $msg, $this->getHandle() ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( 'UTF-8' );
        return $this->xmlAppend( $root )->asXML();
    }

    /**
     * @inheritDoc
     */
    public function xmlAppend( \SimpleXMLElement $node )
    {
        if ( ! $this->isValid() ) {
            return $node;
        }

        $elements = $this->children();
        // remove messages and message references
        unset( $elements[ 'messages' ], $elements[ 'references' ] );

        foreach ( $elements as $child ) {
            $child->xmlAppend( $node );
        }

        return $node;
    }

    /**
     * Get the boolean value for the API request’s msgRefs option. 
     * If a parameter is passed to the function, this method is used as setter.
     * 
     * The default value is TRUE (get references).
     * 
     * @return boolean
     */
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
