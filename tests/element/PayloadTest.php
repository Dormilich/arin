<?php

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Country;
use Dormilich\ARIN\Payloads\Poc;
use PHPUnit\Framework\TestCase;

class PayloadTest extends TestCase
{
    public function testHasAccessor()
    {
        $poc = new Poc;

        $this->assertTrue( $poc->has( 'zip' ) );
        $this->assertTrue( $poc->has( 'postalCode' ) );
        $this->assertTrue( isset( $poc[ 'zip' ] ) );
        $this->assertTrue( isset( $poc[ 'postalCode' ] ) );
        $this->assertFalse( $poc->has( 'xxx' ) );
        $this->assertFalse( isset( $poc[ 'xxx' ] ) );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\NotFoundException
     * @expectedExceptionMessage Element "xxx" not found in the Poc Payload.
     */
    public function testAttrAccessor()
    {
        $poc = new Poc;

        $this->assertInstanceOf( Country::class, $poc->attr( 'country' ) );
        $this->assertInstanceOf( Country::class, $poc->attr( 'iso3166-1' ) );
        $this->assertInstanceOf( Country::class, $poc[ 'country' ] );
        $this->assertInstanceOf( Country::class, $poc[ 'iso3166-1' ] );

        $poc->attr( 'xxx' );
    }

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Value [Dormilich\ARIN\Elements\Element] cannot overwrite a Iso3166-1 Payload.
     */
    public function testGetAndSetAccessor()
    {
        $poc = new Poc;

        $this->assertNull( $poc->get( 'city' ) );
        $this->assertNull( $poc->get( 'state' ) );
        $this->assertNull( $poc->attr( 'country' )->get( 'code2' ) );

        $poc->set( 'city', 'Chantilly' );

        $this->assertSame( 'Chantilly', $poc->get( 'city' ) );

        $poc[ 'state' ] = 'VA';

        $this->assertSame( 'VA', $poc->get( 'state' ) );

        $poc[ 'country' ] = new Country( 'US' );

        $this->assertSame( 'US', $poc->attr( 'country' )->get( 'code2' ) );

        $poc[ 'country' ] = new Element( 'US' );
    }

    public function testUnsetAccessor()
    {
        $poc = new Poc;
        $poc->set( 'city', 'Chantilly' );
        $poc->set( 'state', 'VA' );
        $poc[ 'country' ][ 'code2' ] = 'US';

        $poc->set( 'city', NULL );
        unset( $poc[ 'state' ] );
        $poc[ 'country' ] = NULL;

        $this->assertNull( $poc->get( 'city' ) );
        $this->assertNull( $poc->get( 'state' ) );
        $this->assertNull( $poc->attr( 'country' )->get( 'code2' ) );
    }

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Cannot add a value to a non-group element (city).
     */
    public function testAddAccessor()
    {
        $poc = new Poc;

        $this->assertCount( 0, $poc[ 'email' ] );

        $poc->add( 'email', 'john.doe@example.com' );
        $poc[ 'email' ][] = 'allan.smith@example.com';

        $this->assertCount( 2, $poc[ 'email' ] );

        $poc->add( 'city', 'Chantilly' );

        $this->assertTrue( $poc->attr( 'city' )->isValid() );

        $poc->add( 'city', 'Washington' );
    }

    public function testClone()
    {
        $poc = new Poc( 'TEST-ARIN' );
        $poc->set( 'type', 'ROLE' );
        $poc->set( 'city', 'Chantilly' );

        $this->assertSame( 'TEST-ARIN', $poc->get( 'handle' ) );
        $this->assertSame( 'ROLE', $poc->get( 'type' ) );
        $this->assertSame( 'Chantilly', $poc->get( 'city' ) );

        $copy = clone $poc;

        $this->assertNull( $copy->get( 'handle' ) );
        $this->assertSame( 'ROLE', $copy->get( 'type' ) );
        $this->assertSame( 'Chantilly', $copy->get( 'city' ) );
    }

    public function testGetValueOnPayload()
    {
        $poc = new Poc( 'TEST-ARIN' );
        $poc[ 'type' ] = 'ROLE';
        $poc[ 'country' ][ 'code2' ] = 'US';
        $poc[ 'country' ][ 'code3' ] = 'USA';
        $poc[ 'country' ][ 'e164' ] = '1';
        $poc[ 'state' ] = 'VA';
        $poc[ 'city' ] = 'Chantilly';
        $poc[ 'zip' ] = '20151';

        $file = __DIR__ . '/_fixtures/poc.json';
        $this->assertJsonStringEqualsJsonFile( $file, json_encode( $poc->getValue() ) );
    }

    public function testIteration()
    {
        $country = new Country;
        $country[ 'code2' ] = 'US';
        $country[ 'code3' ] = 'USA';
        $country[ 'e164' ] = '1';

        $dict = iterator_to_array( $country );

        $this->assertCount( 4, $dict );
        $this->assertArrayHasKey( 'code2', $dict );
        $this->assertArrayHasKey( 'code3', $dict );
        $this->assertArrayHasKey( 'e164', $dict );
        $this->assertArrayHasKey( 'name', $dict );
        $this->assertContainsOnlyInstancesOf( Element::class, $dict );
        $this->assertSame( 'US', $dict[ 'code2' ]->getValue() );
        $this->assertSame( 'USA', $dict[ 'code3' ]->getValue() );
        $this->assertSame( 1, $dict[ 'e164' ]->getValue() );
        $this->assertNull( $dict[ 'name' ]->getValue() );
    }

    public function testReadXmlString()
    {
        $file = __DIR__ . '/_fixtures/phone.xml';
        $xml = file_get_contents( $file );

        $phone = Payload::fromXML( $xml );

        $this->assertTrue( $phone->isValid() );
    }

    public function testReadDomXml()
    {
        $file = __DIR__ . '/_fixtures/phone.xml';
        $doc = new \DOMDocument;
        $doc->load( $file );

        $phone1 = Payload::fromXML( $doc );

        $this->assertTrue( $phone1->isValid() );

        $phone2 = Payload::fromXML( $doc->documentElement );

        $this->assertTrue( $phone2->isValid() );
    }

    public function testReadSimpleXml()
    {
        $file = __DIR__ . '/_fixtures/phone.xml';
        $xml = simplexml_load_file( $file );

        $phone = Payload::fromXML( $xml );

        $this->assertTrue( $phone->isValid() );
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessage Empty XML document cannot be parsed into a Payload.
     */
    public function testReadEmptyXmlFails()
    {
        Payload::fromXML( '' );
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionCode 76
     * @expectedExceptionMessage Opening and ending tag mismatch: poc line 1 and org
     */
    public function testReadInvalidXmlFails()
    {
        Payload::fromXML( '<poc></org>' );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\parserException
     * @expectedExceptionMessage Payload "Foo" is not known.
     */
    public function testReadUnknownXmlFails()
    {
        Payload::fromXML( '<foo></foo>' );
    }
}
