<?php

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Transformers as TF;
use PHPUnit\Framework\TestCase;

class TransformerTest extends TestCase
{
    /**
     * @dataProvider stringInputProvider
     */
    public function testStringTransformer( $in, $out )
    {
        $e = new Element( 'string' );
        $e->apply( new TF\StringTransformer );

        $this->assertFalse( $e->isDefined(), 'defined before' );
        $this->assertNull( $e->getValue(), 'value before' );

        $e->setValue( $in );

        $this->assertTrue( $e->isDefined(), 'defined after' );
        $this->assertSame( $out, $e->getValue() );
    }

    /**
     * @dataProvider intInputProvider
     */
    public function testIntegerTransformer( $in, $out )
    {
        $e = new Element( 'int' );
        $e->apply( new TF\IntegerTransformer );

        $this->assertFalse( $e->isDefined(), 'defined before' );
        $this->assertNull( $e->getValue(), 'value before' );

        $e->setValue( $in );

        $this->assertTrue( $e->isDefined(), 'defined after' );
        $this->assertSame( $out, $e->getValue() );
    }

    /**
     * @dataProvider boolInputProvider
     */
    public function testBooleanTransformer( $in, $out )
    {
        $e = new Element( 'bool' );
        $e->apply( new TF\BooleanTransformer );

        $this->assertFalse( $e->isDefined(), 'defined before' );
        $this->assertNull( $e->getValue(), 'value before' );

        $e->setValue( $in );

        $this->assertTrue( $e->isDefined(), 'defined after' );
        $this->assertSame( $out, $e->getValue() );
    }

    /**
     * @dataProvider ipInputProvider
     */
    public function testIpTransformer( $in, $out )
    {
        $e = new Element( 'ip' );
        $e->apply( new TF\IpTransformer );

        $this->assertFalse( $e->isDefined(), 'defined before' );
        $this->assertNull( $e->getValue(), 'value before' );

        $e->setValue( $in );

        $this->assertTrue( $e->isDefined(), 'defined after' );
        $this->assertSame( $out, $e->getValue() );
    }

    public function testCallbackTransformer()
    {
        $e = new Element( 'callback' );
        $e->apply( new TF\CallbackTransformer( 'strtoupper', 'strtolower' ) );

        $e->setValue( 'Foo' );

        $this->assertSame( 'foo', $e->getValue() );

        $xml = simplexml_load_string( '<root/>' );
        $e->xmlAppend( $xml );

        $this->assertSame( 'FOO', (string) $xml->callback, 'xml value' );
    }

    public function testStackTransformer()
    {
        $stack = new TF\StackTransformer;
        $stack->push( new TF\CallbackTransformer( 'strtolower', 'strval' ) );
        $stack->push( new TF\CallbackTransformer( 'ucfirst', 'strval' ) );

        $e = new Element( 'stack' );
        $e->apply( $stack );
        $e->setValue( 'FOO' );

        $this->assertSame( 'Foo', $e->getValue() );
    }

    public function testDatetimeTransformer()
    {
        $setup = new \DateTime( 'now', new \DateTimeZone( 'America/New_York' ) );

        $e = new Element( 'datetime' );
        $e->apply( new TF\DatetimeTransformer( $setup ) );

        $this->assertFalse( $e->isDefined(), 'defined before' );
        $this->assertNull( $e->getValue(), 'value before' );

        $e->setValue( '1997-04-18 13:52:09' );

        $date = $e->getValue();

        $expected = '1997-04-18T13:52:09-04:00';
        $this->assertInstanceOf( 'DateTime', $date, 'class' );
        $this->assertNotSame( $setup, $date, 'separation' );
        $this->assertSame( $expected, $date->format( 'c' ) );

        $xml = simplexml_load_string( '<root/>' );
        $e->xmlAppend( $xml );

        $this->assertSame( $expected, (string) $xml->datetime, 'xml value' );
    }

    public function testIntegerTransformerIgnoresBoolean()
    {
        $e = new Element( 'int' );
        $e->apply( new TF\IntegerTransformer );

        $e->setValue( true );

        $this->assertSame( 'true', $e->getValue() );

        $e->setValue( false );

        $this->assertSame( 'false', $e->getValue() );
    }

    // test payloads

    public function stringInputProvider()
    {
        $mock = $this->createMock( 'Exception' );
        $mock->method( '__toString' )->willReturn( 'phpunit' );

        return [
            [ 'foo', 'foo' ],
            [ false, 'false' ],
            [ 3.14,  '3.14' ],
            [ -42,   '-42' ],
            [ $mock, 'phpunit' ],
        ];
    }

    public function intInputProvider()
    {
        return [
            [ '28',   28 ],
            [ '+51',  51 ],
            [ '-17', -17 ],
            [ 42, 42 ],
            [ +3,  3 ],
            [ -9, -9 ],
        ];
    }

    public function boolInputProvider()
    {
        return [
            [ true,  true ],
            [ false, false ],
            [ 'true',  true ],
            [ 'false', false ],
            [ 'yes', true ],
            [ 'no',  false ],
            [ 'on',  true ],
            [ 'off', false ],
            [ '1', true ],
            [  1,  true ],
            [ '0', false ],
            [  0,  false ],
        ];
    }

    public function ipInputProvider()
    {
        return [
            [ '192.168.002.001', '192.168.2.1' ],
            [ '192.168.19.53', '192.168.19.53' ],
            [ '2001:0db8:85a3:08d3:1319:8a2e:7347:0000', '2001:db8:85a3:8d3:1319:8a2e:7347::' ],
            [ '2001:db8:85a3:8d3::', '2001:db8:85a3:8d3::' ],
        ];
    }
}
