<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Net;
use Dormilich\ARIN\Payloads\NetBlock;
use Dormilich\Http\NetworkInterface;
use Dormilich\Http\RangeInterface;
use PHPUnit\Framework\TestCase;

class NetTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/net.';

    public function testReadNet()
    {
        $xml = file_get_contents( $this->file . 'xml' );
        $n = Payload::fromXML( $xml );

        $this->assertInstanceOf( Net::class, $n );

        return $n;
    }

    /**
     * @depends testReadNet
     */
    public function testNetJson( Net $n )
    {
        $this->assertJsonStringEqualsJsonFile( $this->file . 'json', json_encode( $n ) );

        return $n;
    }

    /**
     * @depends testNetJson
     */
    public function testNetString( Net $n )
    {
        $this->assertSame( 'NET6-2001-400-0', (string) $n );

        return $n;
    }

    /**
     * @depends testNetString
     */
    public function testNetXml( Net $n )
    {
        $xml = $n->xmlSerialize();

        $this->assertTrue( $n->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file . 'xml', $xml );

        return $n;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Net Payload 'NET6-2001-400-0' is not valid for submission.
     */
    public function testInvalidNetEmitsWarningOnXmlSerialise()
    {
        $n = new Net( 'NET6-2001-400-0' );
        // prevent warning-to-exception
        $xml = @$n->xmlSerialize();

        $sxe = simplexml_load_string( $xml );
        $this->assertSame( 0, count( $sxe ) );

        $n->xmlSerialize();
    }

    public function testCreateNetblockFromPublicCidr()
    {
        $net = $this->createMock( NetworkInterface:: class );
        $net->method( 'getNetwork' )->willReturn( '1.0.0.0' );
        $net->method( 'getPrefixLength' )->willReturn( 24 );

        $block = new NetBlock( $net );

        $this->assertNull( $block->get( 'type' ) );
        $this->assertSame( '1.0.0.0', $block->get( 'start' ) );
        $this->assertNull( $block->get( 'end' ) );
        $this->assertSame( 24, $block->get( 'length' ) );

        $this->assertFalse( $block->isValid() );
        $this->assertSame( '', (string) $block );

        $block->set( 'type', 'S' );

        $this->assertTrue( $block->isValid() );
        $this->assertSame( '1.0.0.0/24', (string) $block );
    }

    public function testCreateNetBlocksFromIps()
    {
        $block = new NetBlock;

        $block->set( 'start', '1.0.0.0' );
        $block->set( 'end', '1.0.0.255' );
        $block->set( 'type', 'S' );

        $this->assertSame( 'S', $block->get( 'type' ) );
        $this->assertSame( '1.0.0.0', $block->get( 'start' ) );
        $this->assertSame( '1.0.0.255', $block->get( 'end' ) );
        $this->assertNull( $block->get( 'length' ) );

        $this->assertTrue( $block->isValid() );
        $this->assertSame( '1.0.0.0 - 1.0.0.255', (string) $block );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage Value ['127.0.0.0'] is not allowed for the [startAddress] element.
     */
    public function testCreateNetblockFromNonPublicCidrFails()
    {
        $net = $this->createMock( NetworkInterface:: class );
        $net->method( 'getNetwork' )->willReturn( '127.0.0.0' );
        $net->method( 'getPrefixLength' )->willReturn( 27 );

        $block = new NetBlock( $net );
    }

    // 1.0.0.0/24 is APNIC Labs, should be OK as a public network for testing
    public function testCreateNetBlocksFromRange()
    {
        // new Network( '1.0.0.2/31' )
        $net1 = $this->createMock( NetworkInterface:: class );
        $net1->method( 'getNetwork' )->willReturn( '1.0.0.2' );
        $net1->method( 'getPrefixLength' )->willReturn( 31 );
        // new Network( '1.0.0.4/31' )
        $net2 = $this->createMock( NetworkInterface:: class );
        $net2->method( 'getNetwork' )->willReturn( '1.0.0.4' );
        $net2->method( 'getPrefixLength' )->willReturn( 31 );
        // new Range( '1.0.0.2 - 1.0.0.5' )
        $range = $this->createMock( RangeInterface::class );
        $range->method( 'getNetworks' )->willReturn( [ $net1, $net2 ] );

        $net = new Net;
        $net[ 'net' ] = $range->getNetworks();

        $this->assertCount( 2, $net[ 'net' ] );
        $this->assertSame( 'S', $net[ 'net' ][ 0 ]->get( 'type' ) );
        $this->assertSame( 'S', $net[ 'net' ][ 1 ]->get( 'type' ) );
    }
}
