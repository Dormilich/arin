<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Ticket;
use Dormilich\ARIN\Payloads\Net;
use Dormilich\ARIN\Payloads\TicketedRequest;
use PHPUnit\Framework\TestCase;

class TicketedRequestTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/tr-';

    public function testReadNet()
    {
        $xml = file_get_contents( $this->file . 'net.xml' );
        $tr = Payload::fromXML( $xml );

        $this->assertInstanceOf( Net::class, $tr[ 'net' ] );
        $this->assertTrue( $tr[ 'net' ]->isValid() );

        return $tr;
    }

    /**
     * @depends testReadNet
     * @expectedException Dormilich\ARIN\Exceptions\NotFoundException
     * @expectedExceptionMessage Element "ticket" not found in the TicketedRequest Payload.
     */
    public function testReadUndefinedTicketFails( TicketedRequest $tr )
    {
        $this->assertFalse( $tr->has( 'ticket' ) );

        $tr[ 'ticket' ];
    }

    public function testReadTicket()
    {
        $xml = file_get_contents( $this->file . 'ticket.xml' );
        $tr = Payload::fromXML( $xml );

        $this->assertInstanceOf( Ticket::class, $tr[ 'ticket' ] );

        return $tr;
    }

    /**
     * @depends testReadTicket
     * @expectedException Dormilich\ARIN\Exceptions\NotFoundException
     * @expectedExceptionMessage Element "net" not found in the TicketedRequest Payload.
     */
    public function testReadUndefinedNetFails( TicketedRequest $tr )
    {
        $this->assertFalse( $tr->has( 'net' ) );

        $tr[ 'net' ];
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testSetNetFails()
    {
        $tr = new TicketedRequest;
        $tr->set( 'net', new Net );
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testAddNetFails()
    {
        $tr = new TicketedRequest;
        $tr->add( 'net', new Net );
    }
}
