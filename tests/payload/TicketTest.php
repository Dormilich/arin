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

    /**
     * @depends testFullTicketJson
     */
    public function testGetMessageById( Ticket $t )
    {
        $m = $t[ 'messages' ][ '813084102' ];

        $this->assertSame( 'Haiku', $m->get( 'subject' ) );

        return $t;
    }

    public function testReadRefsTicket()
    {
        $xml = file_get_contents( $this->file . '-msg-refs.xml' );
        $t = Payload::fromXML( $xml );

        $this->assertInstanceOf( Ticket::class, $t );
        $this->assertSame( '813084102', (string) $t[ 'references' ][ 0 ] );

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
     * @depends testRefsTicketJson
     */
    public function testCloseTicket( Ticket $t )
    {
        // quick fix ticket
        $t[ 'resolved' ] = '2012-02-28T17:41:17-05:00';
        # $t[ 'resolution' ] = 'ANSWERED';

        $t->set( 'status', 'CLOSED' );

        // the interesting part here is that messages/references are ignored
        $this->assertTrue( $t->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file . '.xml', $t->xmlSerialize() );

        return $t;
    }

    /**
     * @depends testCloseTicket
     */
    public function testGetAttachmentReference( Ticket $t )
    {
        $r = $t[ 'references' ][ '813084102' ][ 'references' ][ '1272103575' ];

        $this->assertSame( 'haiku.txt', $r->get( 'filename' ) );

        return $t;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Ticket Payload "20111107-X03878" is not valid for submission.
     */
    public function testInvalidTicketEmitsWarningOnXmlSerialise()
    {
        $t = new Ticket( '20111107-X03878' );
        // prevent warning-to-exception
        $xml = @$t->xmlSerialize();

        $sxe = simplexml_load_string( $xml );
        $this->assertSame( 0, count( $sxe ) );

        $t->xmlSerialize();
    }

    public function testParameterMsgRefs()
    {
        $t = new Ticket( '20111107-X03878' );

        $this->assertTrue( $t->msgRefs() );
        $this->assertFalse( $t->msgRefs( false ) );
    }
}
