<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Attachment;
use Dormilich\ARIN\Payloads\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/message.';

    private $text = "Waves of destiny\nso many lifes in motion\npebble in a pond";

    private function file()
    {
        // can't mock SplFileObject since that complains about the parent constructor not being called
        $info = $this->getMockBuilder( 'SplFileInfo' )
            ->disableOriginalConstructor()
            ->setMethods( [
                'isFile',
                'openFile',
                'getBasename',
                'rewind',
                'getSize',
                'fread', // don't want to use two mocks when I can make it work with one
            ] )
            ->getMock();
        $info->method( 'isFile' )->willReturn( true );
        $info->method( 'openFile' )->will( $this->returnSelf() );
        $info->method( 'getBasename' )->willReturn( 'haiku.txt' );
        $info->method( 'getSize' )->willReturn( strlen( $this->text ) );
        $info->method( 'fread' )->willReturn( $this->text );

        return $info;
    }

    public function testMessageXml()
    {
        $m = new Message;
        $m[ 'subject' ] = 'Haiku';
        $m[ 'text' ] = $this->text;
        $m[ 'attachments' ] = $this->file();

        $this->assertSame( 'haiku.txt', $m[ 'attachments' ][ 0 ][ 'filename' ]->getValue() );
        $this->assertSame( $this->text, $m[ 'attachments' ][ 0 ][ 'data' ]->getValue() );
        $this->assertSame( $this->text, (string) $m );

        $xml = $m->xmlSerialize();

        $this->assertTrue( $m->isValid() );
        $this->assertXmlStringEqualsXmlFile( $this->file . 'xml', $xml );

        return $m;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Message Payload "Haiku" is not valid for submission.
     */
    public function testInvalidMessageEmitsWarningOnXmlSerialise()
    {
        $m = new Message;
        $m[ 'subject' ] = 'Haiku';
        // prevent warning-to-exception
        $xml = @$m->xmlSerialize();

        $sxe = simplexml_load_string( $xml );
        $this->assertSame( 0, count( $sxe ) );

        $m->xmlSerialize();
    }

    public function testInvalidFileInputIsIgnored()
    {
        $a = new Attachment( 'this file does not exist' );

        $this->assertFalse( $a[ 'data' ]->isValid() );
        $this->assertFalse( $a[ 'filename' ]->isValid() );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage File [unrockbar.txt] could not be loaded in the Attachment Payload.
     */
    public function testUnreadableFileFails()
    {
        $mock = $this->createMock( 'SplFileInfo' );
        $mock->method( 'isFile' )->willReturn( true );
        $mock->method( 'getBasename' )->willReturn( 'unrockbar.txt' );
        $mock->method( 'openFile' )->will( $this->throwException( new RuntimeException( 'failed to open file' ) ) );

        new Attachment( $mock );
    }
}
