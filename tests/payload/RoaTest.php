<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Roa;
use PHPUnit\Framework\TestCase;

class RoaTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/roa.';

    public function testReadRoa()
    {
        $xml = file_get_contents( $this->file . 'xml' );
        $r = Payload::fromXML( $xml );

        $this->assertInstanceOf( Roa::class, $r );

        return $r;
    }

    /**
     * @depends testReadRoa
     */
    public function testRoaJson( Roa $r )
    {
        $this->assertJsonStringEqualsJsonFile( $this->file . 'json', json_encode( $r ) );

        return $r;
    }

    /**
     * @depends testReadRoa
     */
    public function testRoaDataString( Roa $r )
    {
        $roaData = '1|1340135296|My First ROA|1234|05-25-2011|05-25-2012|10.0.0.0|8|16|';
        $this->assertSame( $roaData, (string) $r[ 'data' ] );

        return $r;
    }

    /**
     * @depends testRoaDataString
     */
    public function testRoaXml( Roa $r )
    {
        $xml = $r->xmlSerialize();

        $this->assertTrue( $r->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file . 'xml', $xml );

        return $r;
    }

    /**
     * @depends testRoaXml
     */
    public function testSetRoaDataString( Roa $r )
    {
        $roaData = '1|1340135296|My Second ROA|4321|05-25-2011|07-02-2014|10.0.0.0|24|29|';

        $r[ 'data' ] = $roaData;

        $this->assertSame( $roaData, (string) $r[ 'data' ] );
        $this->assertSame( 'AS4321', $r[ 'data' ]->get( 'asn' ) );

        return $r;
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage Value [stdClass] is not allowed for the [roaData] element.
     */
    public function testSetInvalidRoaDataFails()
    {
        $roa = new Roa;
        $roa[ 'data' ] = new stdClass;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Roa Payload "My First ROA" is not valid for submission.
     */
    public function testInvalidRoaEmitsWarningOnXmlSerialise()
    {
        $roa = new Roa;
        $roa[ 'data' ] = '1|1340135296|My First ROA|4321|05-25-2011|07-02-2014|10.0.0.0|24|29|';
        // prevent warning-to-exception
        $xml = @$roa->xmlSerialize();

        $sxe = simplexml_load_string( $xml );
        $this->assertSame( 0, count( $sxe ) );

        $roa->xmlSerialize();
    }

    public function testGetDefaultResourceClass()
    {
        $roa = new Roa;

        $this->assertSame( 'AR', $roa->resourceClass() );
    }

    public function testSetResourceClass()
    {
        $roa = new Roa;
        $roa->resourceClass( 'RN' );

        $this->assertSame( 'RN', $roa->resourceClass() );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage Value [XXX] is not a valid ROA resource class.
     */
    public function testSetInvalidResourceClassFails()
    {
        $roa = new Roa;
        $roa->resourceClass( 'XXX' );
    }
}
