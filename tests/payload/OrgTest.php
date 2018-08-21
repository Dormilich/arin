<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Country;
use Dormilich\ARIN\Payloads\Org;
use Dormilich\ARIN\Payloads\PocLinkRef;
use PHPUnit\Framework\TestCase;

class OrgTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/org.';

    public function testReadOrg()
    {
        $xml = file_get_contents( $this->file . 'xml' );
        $o = Payload::fromXML( $xml );

        $this->assertInstanceOf( Org::class, $o );

        return $o;
    }

    /**
     * @depends testReadOrg
     */
    public function testOrgJson( Org $o )
    {
        $this->assertJsonStringEqualsJsonFile( $this->file . 'json', json_encode( $o ) );

        return $o;
    }

    /**
     * @depends testOrgJson
     */
    public function testOrgString( Org $o )
    {
        $this->assertSame( 'ARIN', (string) $o );

        return $o;
    }

    /**
     * @depends testOrgString
     */
    public function testOrgXml( Org $o )
    {
        $xml = $o->xmlSerialize();

        $this->assertTrue( $o->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file . 'xml', $xml );

        return $o;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Org Payload 'ARIN' is not valid for submission.
     */
    public function testInvalidOrgEmitsWarningOnXmlSerialise()
    {
        $o = new Org( 'ARIN' );
        // prevent warning-to-exception
        $xml = @$o->xmlSerialize();

        $sxe = simplexml_load_string( $xml );
        $this->assertSame( 0, count( $sxe ) );

        $o->xmlSerialize();
    }

    /**
     * @dataProvider dataCountry
     */
    public function testCountryIsValid( $in )
    {
        $c = new Country( $in );

        $this->assertTrue( $c->isValid() );
        $this->assertSame( $in, (string) $c );
    }

    public function dataCountry()
    {
        return [
            [ 'US' ],
            [ 'USA' ],
        ];
    }

    public function testCreatedOrgIsValid()
    {
        $o = new Org();
        $o
            ->set( 'name', 'American Registry for Internet Numbers' )
            ->set( 'state', 'VA' )
            ->set( 'city', 'Centreville' )
            ->set( 'address', 'PO Box 232290' )
        ;
        $o[ 'country' ][ 'code3' ] = 'USA';

        $this->assertFalse( $o->isValid(), 'missing poclink' );

        $ad = new PocLinkRef('ARIN-HOSTMASTER');
        $ad->set( 'function', 'AD' );
        $o->set( 'poc', $ad );

        $this->assertTrue( $o->isValid(), 'valid create' );

        $o->set( 'created', '1997-12-22T00:00:00-05:00' );

        $this->assertFalse( $o->isValid(), 'invalid create' );
    }
}
