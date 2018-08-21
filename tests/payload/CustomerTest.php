<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Customer;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/customer.';

    public function testReadCustomer()
    {
        $xml = file_get_contents( $this->file . 'xml' );
        $c = Payload::fromXML( $xml );

        $this->assertInstanceOf( Customer::class, $c );

        return $c;
    }

    /**
     * @depends testReadCustomer
     */
    public function testCustomerJson( Customer $c )
    {
        $this->assertJsonStringEqualsJsonFile( $this->file . 'json', json_encode( $c ) );

        return $c;
    }

    /**
     * @depends testCustomerJson
     */
    public function testCustomerString( Customer $c )
    {
        $this->assertSame( 'C02478949', (string) $c );

        return $c;
    }

    /**
     * @depends testCustomerString
     */
    public function testCustomerXml( Customer $c )
    {
        $xml = $c->xmlSerialize();

        $this->assertTrue( $c->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file . 'xml', $xml );

        return $c;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Customer Payload 'C02478949' is not valid for submission.
     */
    public function testInvalidCustomerEmitsWarningOnXmlSerialise()
    {
        $c = new Customer( 'C02478949' );
        // prevent warning-to-exception
        $xml = @$c->xmlSerialize();

        $sxe = simplexml_load_string( $xml );
        $this->assertSame( 0, count( $sxe ) );

        $c->xmlSerialize();
    }

    public function testCreatedCustomerIsValid()
    {
        $c = new Customer();
        $c
            ->set( 'name', 'American Registry for Internet Numbers' )
            ->set( 'state', 'VA' )
            ->set( 'city', 'Chantilly' )
            ->set( 'address', [ '3635 Concorde Pkwy', 'Ste 200' ] )
        ;
        $c[ 'country' ][ 'code3' ] = 'USA';

        $this->assertFalse( $c->isValid(), 'missing org' );

        $c->set( 'org', 'ARIN' );

        $this->assertTrue( $c->isValid(), 'valid create' );

        $c->set( 'created', '2010-04-23T20:22:24-04:00' );

        $this->assertFalse( $c->isValid(), 'invalid create' );
    }
}
