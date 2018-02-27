<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Ticket;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/ticket';

    public function testReadFullTicket()
    {
        $xml = file_get_contents( $this->file . '-msg-full.xml' );
        $t = Payload::fromXML( $xml );

        $this->assertInstanceOf( Ticket::class, $t );

        return $t;
    }

    /**
     * @depends testReadFullTicket
     */
    public function testFullTicketJson( Ticket $t )
    {
        $this->assertJsonStringEqualsJsonFile( $this->file . '-msg-full.json', json_encode( $t ) );

        return $t;
    }

    public function testReadRefsTicket()
    {
        $xml = file_get_contents( $this->file . '-msg-refs.xml' );
        $t = Payload::fromXML( $xml );

        $this->assertInstanceOf( Ticket::class, $t );
        $this->assertSame( 'MESSAGEID', (string) $t[ 'references' ][ 0 ] );

        return $t;
    }

    /**
     * @depends testReadRefsTicket
     */
    public function testRefsTicketJson( Ticket $t )
    {
        $this->assertJsonStringEqualsJsonFile( $this->file . '-msg-refs.json', json_encode( $t ) );

        return $t;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Ticket Payload "TICKETNO" is not valid for submission.
     */
    public function testInvalidTicketEmitsWarningOnXmlSerialise()
    {
        $t = new Ticket( 'TICKETNO' );
        // prevent warning-to-exception
        $xml = @$t->xmlSerialize();

        $sxe = simplexml_load_string( $xml );
        $this->assertSame( 0, count( $sxe ) );

        $t->xmlSerialize();
    }

    public function testParameterMsgRefs()
    {
        $t = new Ticket( 'TICKETNO' );

        $this->assertTrue( $t->msgRefs() );
        $this->assertFalse( $t->msgRefs( false ) );
    }

    public function testCloseTicket()
    {
        $t = new Ticket( 'TICKETNO' );

        $t[ 'created' ] = '2011-11-07T14:04:29-05:00';
        $t[ 'updated' ] = '2011-11-07T14:04:29-05:00';
        $t[ 'resolved' ] = '2011-11-07T14:04:29-05:00';
        $t[ 'type' ] = 'POC_RECOVERY';
        $t[ 'status' ] = 'CLOSED';
        $t[ 'resolution' ] = 'ANSWERED';

        $xml = $t->xmlSerialize();

        $this->assertTrue( $t->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file . '.xml', $xml );
    }
}
