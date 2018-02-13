<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Error;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/error.';

    public function testReadError()
    {
        $xml = file_get_contents( $this->file . 'xml' );
        $e = Payload::fromXML( $xml );

        $this->assertInstanceOf( Error::class, $e );
        $this->assertFalse( $e->isValid() );

        return $e;
    }

    /**
     * @depends testReadError
     */
    public function testCustomerJson( Error $e )
    {
        $this->assertJsonStringEqualsJsonFile( $this->file . 'json', json_encode( $e ) );

        return $e;
    }

    /**
     * @depends testCustomerJson
     */
    public function testErrorString( Error $e )
    {
        $msg = 'E_BAD_REQUEST: The Payload could not be processed';
        $this->assertSame( $msg, (string) $e );

        return $e;
    }

    /**
     * @depends testErrorString
     */
    public function testGetExceptionInfo( Error $e )
    {
        $msg = 'The Payload could not be processed';
        $this->assertSame( $msg, $e->getMessage() );
        $this->assertSame( 400, $e->getCode() );

        return $e;
    }
}
