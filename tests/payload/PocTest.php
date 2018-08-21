<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Phone;
use Dormilich\ARIN\Payloads\Poc;
use PHPUnit\Framework\TestCase;

class PocTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/poc.';

    public function testReadPoc()
    {
        $xml = file_get_contents( $this->file . 'xml' );
        $p = Payload::fromXML( $xml );

        $this->assertInstanceOf( Poc::class, $p );

        return $p;
    }

    /**
     * @depends testReadPoc
     */
    public function testPocJson( Poc $p )
    {
        $this->assertJsonStringEqualsJsonFile( $this->file . 'json', json_encode( $p ) );

        return $p;
    }

    /**
     * @depends testPocJson
     */
    public function testPocString( Poc $p )
    {
        $this->assertSame( 'ARIN-HOSTMASTER', (string) $p );

        return $p;
    }

    /**
     * @depends testPocString
     */
    public function testPocXml( Poc $p )
    {
        $xml = $p->xmlSerialize();

        $this->assertTrue( $p->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file . 'xml', $xml );

        return $p;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Poc Payload 'ARIN-HOSTMASTER' is not valid for submission.
     */
    public function testInvalidPocEmitsWarningOnXmlSerialise()
    {
        $p = new Poc( 'ARIN-HOSTMASTER' );
        // prevent warning-to-exception
        $xml = @$p->xmlSerialize();

        $sxe = simplexml_load_string( $xml );
        $this->assertSame( 0, count( $sxe ) );

        $p->xmlSerialize();
    }

    public function testParameterMakeLink()
    {
        $p = new Poc( 'ARIN-HOSTMASTER' );

        $this->assertSame( 'true', $p->makeLink() );
        $this->assertSame( 'false', $p->makeLink( false ) );
    }

    public function testPersonTypePocValidity()
    {
        $xml = file_get_contents( __DIR__ . '/_fixtures/person.xml' );
        $p = Payload::fromXML( $xml );

        $this->assertTrue( $p->isValid() );

        return $p;
    }

    /**
     * @depends testPersonTypePocValidity
     */
    public function testInvalidTypeFailsValidation( Poc $p )
    {
        $test = new Poc();

        $test[ 'address' ] = $p[ 'address' ];
        $test[ 'country' ] = $p[ 'country' ];
        $test[ 'city' ]    = $p[ 'city' ];

        $test[ 'firstName' ] = $p[ 'firstName' ];
        $test[ 'lastName' ]  = $p[ 'lastName' ] ;
        $test[ 'company' ]   = $p[ 'company' ]  ;

        $test[ 'email' ] = $p[ 'email' ];
        $test[ 'phone' ] = $p[ 'phone' ];

        // hacking type's validation to insert an invalid value
        $test[ 'type' ]->test( 'is_string' )->setValue( 'foo' );

        $this->assertFalse( $test->isValid() );
    }

    public function testCreatedRoleIsValid()
    {
        $role = new Poc();
        $role
            ->set( 'company', 'American Registry for Internet Numbers' )
            ->set( 'lastName', 'Registration Services Department' )
            ->set( 'state', 'VA' )
            ->set( 'city', 'Chantilly' )
            ->set( 'address', [ '3635 Concorde Pkwy', 'Ste 200' ] )
            ->set( 'email', 'hostmaster@arin.net' )
            ->set( 'type', 'role' )
        ;
        $role[ 'country' ][ 'code3' ] = 'USA';

        $this->assertFalse( $role->isValid(), 'missing phone' );

        $phone = new Phone( '+1-703-227-0660' );
        $phone[ 'type' ][ 'code' ] = 'O';
        $role->set( 'phone', $phone );

        $this->assertTrue( $role->isValid(), 'valid create' );

        $role->set( 'firstName', 'John' );

        $this->assertFalse( $role->isValid(), 'invalid create' );
    }

    public function testCreatedPersonIsValid()
    {
        $person = new Poc();
        $person
            ->set( 'lastName', 'Kosters' )
            ->set( 'company', 'American Registry for Internet Numbers' )
            ->set( 'state', 'VA' )
            ->set( 'city', 'Chantilly' )
            ->set( 'address', [ '3635 Concorde Pkwy', 'Ste 200' ] )
            ->set( 'email', 'hostmaster@arin.net' )
            ->set( 'type', 'person' )
        ;
        $person[ 'country' ][ 'code3' ] = 'USA';

        $phone = new Phone( '+1-703-227-0660' );
        $phone[ 'type' ][ 'code' ] = 'O';
        $person->set( 'phone', $phone );

        $this->assertFalse( $person->isValid(), 'missing name' );

        $person->set( 'firstName', 'Mark' );

        $this->assertTrue( $person->isValid(), 'valid create' );

        $person->set( 'created', '2009-10-02T10:54:45-04:00' );

        $this->assertFalse( $person->isValid(), 'invalid create' );
    }
}
