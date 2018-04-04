<?php

use Dormilich\ARIN\Elements\Payload;
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
        $test = new Poc( $p );

        $test[ 'address' ] = $p[ 'address' ];
        $test[ 'country' ] = $p[ 'country' ];
        $test[ 'city' ]    = $p[ 'city' ];

        $test[ 'firstName' ] = $p[ 'firstName' ];
        $test[ 'lastName' ]  = $p[ 'lastName' ] ;
        $test[ 'company' ]   = $p[ 'company' ]  ;

        $test[ 'email' ] = $p[ 'email' ];
        $test[ 'phone' ] = $p[ 'phone' ];

        // this is hacking the validation
        $test[ 'type' ]->test( 'is_string' )->setValue( 'foo' );

        $this->assertFalse( $test->isValid() );
    }
}
