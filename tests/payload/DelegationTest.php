<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Delegation;
use PHPUnit\Framework\TestCase;

class DelegationTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/delegation.';

    public function testReadDelegation()
    {
        $xml = file_get_contents( $this->file . 'xml' );
        $d = Payload::fromXML( $xml );

        $this->assertInstanceOf( Delegation::class, $d );

        return $d;
    }

    /**
     * @depends testReadDelegation
     */
    public function testDelegationJson( Delegation $d )
    {
        $this->assertJsonStringEqualsJsonFile( $this->file . 'json', json_encode( $d ) );

        return $d;
    }

    /**
     * @depends testDelegationJson
     */
    public function testDelegationString( Delegation $d )
    {
        $this->assertSame( '0.76.in-addr.arpa.', (string) $d );

        return $d;
    }

    /**
     * @depends testDelegationString
     */
    public function testDelegationXml( Delegation $d )
    {
        $xml = $d->xmlSerialize()->asXML();

        $this->assertTrue( $d->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file . 'xml', $xml );

        return $d;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Delegation Payload '0.76.in-addr.arpa.' is not valid for submission.
     */
    public function testInvalidDelegationEmitsWarningOnXmlSerialise()
    {
        $d = new Delegation( '0.76.in-addr.arpa.' );
        // prevent warning-to-exception
        $xml = @$d->xmlSerialize();

        $this->assertInstanceOf( 'SimpleXMLElement', $xml );
        $this->assertSame( 0, count( $xml ) );

        $d->xmlSerialize();
    }
}
