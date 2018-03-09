<?php

use Dormilich\ARIN\Primary;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Payloads\Customer;
use Dormilich\ARIN\Payloads\Net;
use Dormilich\ARIN\Payloads\Org;
use Dormilich\ARIN\Payloads\Poc;
use Dormilich\ARIN\Transformers as TF;
use PHPUnit\Framework\TestCase;

class TransformerTest extends TestCase
{
    public function testTransformerIsCalledInElement()
    {
        $mock = $this->createMock( TF\DataTransformerInterface::class );
        $mock->expects( $this->once() )
             ->method( 'transform' )
             ->with( $this->identicalTo( true ) )
             ->willReturn( 'true' );
        $mock->expects( $this->once() )
             ->method( 'reverseTransform' )
             ->with( $this->identicalTo( 'true' ) )
             ->willReturn( true );

        $e = new Element( 'test' );
        $e->apply( $mock );
        $e->setValue( true );
        $value = $e->getValue();

        $this->assertTrue( $value );
    }

    /**
     * @dataProvider stringInputProvider
     */
    public function testStringTransformer( $in, $out )
    {
        $tf = new TF\StringTransformer;

        $internal = $tf->transform( $in );
        $value = $tf->reverseTransform( $internal );

        $this->assertSame( $out, $internal , 'internal' );
        $this->assertSame( $out, $value, 'output' );
    }

    /**
     * @dataProvider intInputProvider
     */
    public function testIntegerTransformer( $in, $val, $out )
    {
        $tf = new TF\IntegerTransformer;

        $internal = $tf->transform( $in );
        $value = $tf->reverseTransform( $internal );

        $this->assertSame( $val, $internal , 'internal' );
        $this->assertSame( $out, $value, 'output' );
    }

    public function testIntegerTransformerIgnoresBoolean()
    {
        $tf = new TF\IntegerTransformer;

        // because true == 1, false == 0
        $true = $tf->transform( true );
        $false = $tf->transform( false );

        $this->assertTrue( $true );
        $this->assertFalse( $false );
    }

    /**
     * @dataProvider boolInputProvider
     */
    public function testBooleanTransformer( $in, $val, $out )
    {
        $tf = new TF\BooleanTransformer;

        $internal = $tf->transform( $in );
        $value = $tf->reverseTransform( $internal );

        $this->assertSame( $val, $internal , 'internal' );
        $this->assertSame( $out, $value, 'output' );
    }

    /**
     * @dataProvider ipInputProvider
     */
    public function testIpTransformer( $in, $out )
    {
        $tf = new TF\IpTransformer;

        $internal = $tf->transform( $in );
        $value = $tf->reverseTransform( $internal );

        $this->assertTrue( is_string( $internal ), 'internal' );
        $this->assertSame( $out, $value, 'output' );
    }

    public function testCallbackTransformer()
    {
        $tf = new TF\CallbackTransformer( 'strtoupper', 'strtolower' );

        $internal = $tf->transform( 'Foo' );
        $value = $tf->reverseTransform( $internal );

        $this->assertSame( 'FOO', $internal, 'internal' );
        $this->assertSame( 'foo', $value, 'output' );
    }

    public function testNonTransformer()
    {
        $tf = new TF\StackTransformer;
        $input = new stdClass;

        $internal = $tf->transform( $input );
        $value = $tf->reverseTransform( $internal );

        $this->assertSame( $input, $internal, 'internal' );
        $this->assertSame( $input, $value, 'output' );
    }

    public function testStackTransformer()
    {
        $stack = new TF\StackTransformer;
        $stack->push( new TF\CallbackTransformer( 'strtolower', 'strtoupper' ) );
        $stack->push( new TF\CallbackTransformer( 'ucfirst', 'strval' ) );

        $internal = $stack->transform( 'FOO' );
        $value = $stack->reverseTransform( $internal );

        $this->assertSame( 'Foo', $internal, 'full stack' );
        $this->assertSame( 'FOO', $value, 'output' );
        $this->assertCount( 2, $stack );

        $stack->pop();

        $internal = $stack->transform( 'FOO' );

        $this->assertSame( 'foo', $internal, 'reduced stack' );
        $this->assertCount( 1, $stack );
    }

    public function testDefaultDatetimeTransformer()
    {
        $tf = new TF\DatetimeTransformer;

        $internal = $tf->transform( '1997-04-18 13:52:09' );    // UTC => EST
        $date = $tf->reverseTransform( $internal );             // EST => UTC

        $this->assertSame( '1997-04-18T09:52:09-04:00', $internal, 'internal' );
        $this->assertInstanceOf( 'DateTimeImmutable', $date, 'class' );
        $this->assertSame( '1997-04-18T13:52:09+00:00', $date->format( 'c' ), 'output' );
    }

    public function testConfiguredDatetimeTransformer()
    {
        $setup = new DateTime( 'now', new \DateTimeZone( 'Indian/Christmas' ) );
        $tf = new TF\DatetimeTransformer( $setup );

        $internal = $tf->transform( '1997-04-18 13:52:09' );
        $date = $tf->reverseTransform( $internal );

        $this->assertSame( '1997-04-18T02:52:09-04:00', $internal, 'internal' );
        $this->assertInstanceOf( 'DateTime', $date, 'class' );
        $this->assertNotSame( $setup, $date, 'separation' );
        $this->assertSame( '1997-04-18T13:52:09+07:00', $date->format( 'c' ), 'output' );
    }

    /**
     * @depends testDefaultDatetimeTransformer
     */
    public function testDatetimeTransformNullDate()
    {
        $tf = new TF\DatetimeTransformer;
        // the native date classes would convert NULL into the current timestamp
        $value = $tf->reverseTransform( NULL );

        $this->assertNull( $value );
    }

    public function testDatetimeTransformObject()
    {
        $setup = new DateTime( 'now', new DateTimeZone( 'Indian/Christmas' ) );
        $tf = new TF\DatetimeTransformer( $setup );

        $data = new DateTimeImmutable( '1997-04-18 13:52:09', new DateTimeZone( 'Europe/Paris' ) );
        $pass = $tf->transform( $data );
        $date = $tf->reverseTransform( $pass );

        $this->assertSame( '1997-04-18T13:52:09+02:00', $data->format( 'c' ), 'input' );
        $this->assertSame( '1997-04-18T07:52:09-04:00', $pass, 'internal' );
        $this->assertSame( '1997-04-18T18:52:09+07:00', $date->format( 'c' ), 'output' );
    }

    public function testDatetimeTransformerIgnoresInvalidDate()
    {
        $tf = new TF\DatetimeTransformer;

        $fail = $tf->transform( 'invalid' );

        $this->assertSame( 'invalid', $fail );
    }

    public function testElementTransformer()
    {
        $setup = new Element( 'hex' );

        $tf = new TF\ElementTransformer( $setup );

        $elem = $tf->transform( 'abc' );
        $fail = $tf->transform( new stdClass );
        $value = $tf->reverseTransform( $elem );

        $this->assertNotSame( $setup, $elem );
        $this->assertInstanceOf( Element::class, $elem, 'class' );
        $this->assertSame( 'hex', $elem->getName(), 'name' );
        $this->assertSame( 'abc', $elem->getValue(), 'value' );

        $this->assertNotInstanceOf( Element::class, $fail, 'invalid' );
    }

    public function testElementTransformerWithValidator()
    {
        $setup = new Element( 'hex' );
        $setup->test( 'ctype_xdigit' );

        $tf = new TF\ElementTransformer( $setup );
        $data = $tf->transform( 'xyz' );

        $this->assertSame( 'xyz', $data);
    }

    /**
     * @dataProvider handleInputProvider
     */
    public function testHandleTransformerInput( $in, $val, $class )
    {
        $tf = new TF\HandleTransformer;

        $internal = $tf->transform( $in );
        $obj = $tf->reverseTransform( $internal );

        $this->assertSame( $val, $internal, 'internal' );
        $this->assertInstanceOf( $class, $obj, 'output class' );
        $this->assertSame( $val, $obj->getHandle(), 'output handle' );
    }

    public function testHandleTransformerIgnoresUnknownHandle()
    {
        $tf = new TF\HandleTransformer;

        $obj = $tf->reverseTransform( 'a-b' );

        $this->assertFalse( is_object( $obj ) );
    }

    public function testMapTransformer()
    {
        $tf = new TF\MapTransformer([
            'a' => 1, 'x' => 1,
            'b' => 2, 'y' => 2,
            'c' => 3, 'z' => 3,
        ]);

        $num = $tf->transform( 'x' );
        $fail = $tf->transform( 'o' );
        $key = $tf->reverseTransform( $num );

        $this->assertSame( 1, $num, 'internal' );
        $this->assertSame( 'o', $fail, 'failure' );
        $this->assertSame( 'a', $key, 'output' );
    }

    // test payloads

    public function stringInputProvider()
    {
        $mock = $this->createMock( 'Exception' );
        $mock->method( '__toString' )->willReturn( 'phpunit' );

        $obj = $this->createMock( Primary::class );
        $obj->method( 'getHandle' )->willReturn( 'TEST-ARIN' );

        return [
            [ 'foo', 'foo' ],
            [ false, 'false' ],
            [ 3.14,  '3.14' ],
            [ -42,   '-42' ],
            [ $mock, 'phpunit' ],
            [ $obj,  'TEST-ARIN' ],
        ];
    }

    public function intInputProvider()
    {
        return [
            [ '28', '28', 28 ],
            [ '+51', '51', 51 ],
            [ '-17', '-17', -17 ],
            [ 42, '42', 42 ],
            [ +3, '3', 3 ],
            [ -9, '-9', -9 ],
        ];
    }

    public function boolInputProvider()
    {
        return [
            [ true, 'true', true ],
            [ false, 'false', false ],
            [ 'true', 'true', true ],
            [ 'false', 'false', false ],
            [ 'yes', 'true', true ],
            [ 'no', 'false', false ],
            [ 'on', 'true', true ],
            [ 'off', 'false', false ],
            [ '1', 'true', true ],
            [  1, 'true', true ],
            [ '0', 'false', false ],
            [  0, 'false', false ],
        ];
    }

    public function ipInputProvider()
    {
        return [
            [ '192.168.002.001', '192.168.2.1' ],
            [ '192.168.19.53', '192.168.19.53' ],
            // always fails in Travis
        #   [ '2001:0db8:85a3:08d3:1319:8a2e:0000:7347', '2001:db8:85a3:8d3:1319:8a2e::7347' ],
            [ '2001:0db8:85a3:08d3::', '2001:db8:85a3:8d3::' ],
        ];
    }

    public function boolProvider()
    {
        return [
            [ true ],
            [ false ],
        ];
    }

    public function handleInputProvider()
    {
        $obj = $this->createMock( Primary::class );
        $obj->expects( $this->once() )->method( 'getHandle' )->willReturn( 'TEST-ARIN' );

        return [
            [ $obj, 'TEST-ARIN', Poc::class ],
            [ 'org-42', 'ORG-42', Org::class ],
            [ 'C12345', 'C12345', Customer::class ],
            [ 'NET-10-0-0-0-1', 'NET-10-0-0-0-1', Net::class ],
            [ 'NET6-2001-db8-1', 'NET6-2001-DB8-1', Net::class ],
        ];
    }
}
