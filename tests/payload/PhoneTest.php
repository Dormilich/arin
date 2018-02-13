<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/phone.xml';

    public function testCreatePhone()
    {
        $phone = new Phone;
        $phone[ 'number' ] = '+1.703.227.9840';
        $phone[ 'extension' ] = 101;
        $phone[ 'type' ][ 'code' ] = 'O';

        $this->assertSame( '+1.703.227.9840', (string) $phone );

        return $phone;
    }

    /**
     * @depends testCreatePhone
     */
    public function testPhoneXml( Phone $phone )
    {
        $xml = $phone->xmlSerialize()->asXML();

        $this->assertTrue( $phone->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file, $xml );

        return $phone;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Phone Payload '+1.703.227.9840' is not valid for submission.
     */
    public function testInvalidPhoneEmitsWarningOnXmlSerialise()
    {
        $phone = new Phone( '+1.703.227.9840' );
        // prevent warning-to-exception
        $xml = @$phone->xmlSerialize();

        $this->assertInstanceOf( 'SimpleXMLElement', $xml );
        $this->assertSame( 0, count( $xml ) );

        $phone->xmlSerialize();
    }
}
