<?php

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\ReadOnly;
use Dormilich\ARIN\Validators as VD;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private function mockValidator( $isValid, $value )
    {
        $mock = $this->getMockBuilder( 'stdClass' )
             ->setMethods( ['__invoke'] )
             ->getMock();
        $mock->expects( $this->once() )
             ->method( '__invoke' )
             ->with( $this->identicalTo( $value ) )
             ->willReturn( (bool) $isValid );

        return $mock;
    }

    public function testValidatorIsCalledOnInput()
    {
        $mock = $this->mockValidator( true, 'true' );

        $e = new Element( 'test' );
        $e->test( $mock );
        $e->setValue( true );
    }

    public function testValidatorIsCalledOnValidate()
    {
        $mock = $this->mockValidator( true, 'true' );

        $e = new Element( 'test' );
        $e->setValue( true );
        $e->test( $mock );

        $this->assertTrue( $e->isValid() );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage Value ['foo'] is not allowed for the [object] element.
     */
    public function testElementsThrowsExceptionWhenValidationFailsOnInput()
    {
        $e = new Element( 'object' );
        // passes due to the default validator
        $e->setValue( 'bar' );
        $this->assertTrue( $e->isValid() );

        // forces failure since the input must be a string
        $e->test( 'is_object' );
        $this->assertFalse( $e->isValid() );

        $e->setValue( 'foo' );
    }

    private function callValidator( $vd, $value )
    {
        return call_user_func( $vd, $value );
    }

    public function testChoice()
    {
        $vd = new VD\Choice( [ 'choices' => [ 'foo', 'bar' ] ] );

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, 'foo' );
        $fail = $this->callValidator( $vd, 'invalid' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    /**
     * @depends testChoice
     */
    public function testChoiceWithSingleValue()
    {
        $vd = new VD\Choice( [ 'choices' => 'foo' ] );

        $pass = $this->callValidator( $vd, 'foo' );
        $fail = $this->callValidator( $vd, 'invalid' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    public function testDatetime()
    {
        $vd = new VD\Datetime;

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, '1997-04-18' );
        $fail = $this->callValidator( $vd, 'invalid' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    public function testDatetimeWithFormat()
    {
        $vd = new VD\Datetime( [ 'format' => 'Y-m-d' ] );

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, '1997-04-18' );
        $fail = $this->callValidator( $vd, 'invalid' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    public function testEmail()
    {
        $vd = new VD\Email;

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, 'me@example.org' );
        $fail = $this->callValidator( $vd, 'invalid' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    public function testIpIsCallable()
    {
        $vd = new VD\Ip;

        $this->assertTrue( is_callable( $vd ), 'is callable' );
    }

    /**
     * @dataProvider ipValueProvider
     */
    public function testIp( $version, $value, $result )
    {
        $vd = new VD\Ip( [ 'version' => $version ] );

        $test = $this->callValidator( $vd, $value );

        $this->assertSame( $result, $test );
    }

    public function testRange()
    {
        $vd = new VD\Range( [ 'min' => 5, 'max' => 17 ] );

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, '12' );
        $fail = $this->callValidator( $vd, '3' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage preg_match(): No ending delimiter '^' found
     */
    public function testCheckRegexpPattern()
    {
        new VD\RegExp( [ 'pattern' => '^AS[1-9]\d*$' ] );
    }

    public function testRegexp()
    {
        $vd = new VD\RegExp( [ 'pattern' => '/^AS[1-9]\d*$/' ] );

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, 'AS123' );
        $fail = $this->callValidator( $vd, 'invalid' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    public function testUnsigned()
    {
        $vd = 'ctype_digit';

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, '42' );
        $fail = $this->callValidator( $vd, '-9' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    public function testNamedElement()
    {
        $vd = new VD\NamedElement( [ 'name' => 'phpunit' ] );

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, new Element( 'phpunit' ) );
        $fail = $this->callValidator( $vd, new Element( 'foo' ) );
        $invalid = $this->callValidator( $vd, 'phpunit' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
        $this->assertFalse( $invalid );
    }

    public function testClassList()
    {
        $vd = new VD\ClassList( [ 'choices' => [
            Generated::class,
            ReadOnly::class,
        ] ] );

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, new ReadOnly( 'phpunit' ) );
        $fail = $this->callValidator( $vd, new Element( 'phpunit' ) );
        $invalid = $this->callValidator( $vd, 'phpunit' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
        $this->assertFalse( $invalid );
    }

    public function testClassListWithSingleValue()
    {
        $vd = new VD\ClassList( [ 'choices' => Element::class ] );

        $pass = $this->callValidator( $vd, new Element( 'test' ) );
        $fail = $this->callValidator( $vd, 'invalid' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    public function testBase64()
    {
        $vd = new VD\Base64;

        $this->assertTrue( is_callable( $vd ), 'is callable' );

        $pass = $this->callValidator( $vd, 'REFUQQ==' );
        $fail = $this->callValidator( $vd, '\invalid' );

        $this->assertTrue( $pass );
        $this->assertFalse( $fail );
    }

    public function ipValueProvider()
    {
        // private: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, fc00::/7, fd00::/7
        // reserved: 0.0.0.0/8, 127.0.0.0/8, 169.254.0.0/16, 240.0.0.0/4, 2001:0db8::/32, ::1/128
        // setup - value
        return [
            [ NULL, '207.142.131.235', true ],  // public
            [ NULL, '127.0.0.1', true ],        // reserved
            [ NULL, '192.168.2.1', true ],      // private
            [ NULL, '7708:7d89:363d:b185::', true ], // public
            [ NULL, '2001:0db8:85a3:8d3::', true ],  // reserved
            [ NULL, 'fd9e:21a7:a92c:2323::', true ], // private
            [ VD\Ip::ALL, '207.142.131.235', true ],
            [ VD\Ip::ALL, '127.0.0.1', true ],
            [ VD\Ip::ALL, '192.168.2.1', true ],
            [ VD\Ip::ALL, '7708:7d89:363d:b185::', true ],
            [ VD\Ip::ALL, '2001:0db8:85a3:8d3::', true ],
            [ VD\Ip::ALL, 'fd9e:21a7:a92c:2323::', true ],
            [ VD\Ip::V4, '207.142.131.235', true ],
            [ VD\Ip::V4, '127.0.0.1', true ],
            [ VD\Ip::V4, '192.168.2.1', true ],
            [ VD\Ip::V4, '7708:7d89:363d:b185::', false ],
            [ VD\Ip::V4, '2001:0db8:85a3:8d3::', false ],
            [ VD\Ip::V4, 'fd9e:21a7:a92c:2323::', false ],
            [ VD\Ip::V6, '207.142.131.235', false ],
            [ VD\Ip::V6, '127.0.0.1', false ],
            [ VD\Ip::V6, '192.168.2.1', false ],
            [ VD\Ip::V6, '7708:7d89:363d:b185::', true ],
            [ VD\Ip::V6, '2001:0db8:85a3:8d3::', true ],
            [ VD\Ip::V6, 'fd9e:21a7:a92c:2323::', true ],
            [ VD\Ip::ALL_ONLY_PUBLIC, '207.142.131.235', true ],
            [ VD\Ip::ALL_ONLY_PUBLIC, '127.0.0.1', false ],
            [ VD\Ip::ALL_ONLY_PUBLIC, '192.168.2.1', false ],
            [ VD\Ip::ALL_ONLY_PUBLIC, '7708:7d89:363d:b185::', true ],
            [ VD\Ip::ALL_ONLY_PUBLIC, '2001:0db8:85a3:8d3::', false ],
            [ VD\Ip::ALL_ONLY_PUBLIC, 'fd9e:21a7:a92c:2323::', false ],
            [ VD\Ip::V4_ONLY_PUBLIC, '207.142.131.235', true ],
            [ VD\Ip::V4_ONLY_PUBLIC, '127.0.0.1', false ],
            [ VD\Ip::V4_ONLY_PUBLIC, '192.168.2.1', false ],
            [ VD\Ip::V4_ONLY_PUBLIC, '7708:7d89:363d:b185::', false ],
            [ VD\Ip::V4_ONLY_PUBLIC, '2001:0db8:85a3:8d3::', false ],
            [ VD\Ip::V4_ONLY_PUBLIC, 'fd9e:21a7:a92c:2323::', false ],
            [ VD\Ip::V6_ONLY_PUBLIC, '207.142.131.235', false ],
            [ VD\Ip::V6_ONLY_PUBLIC, '127.0.0.1', false ],
            [ VD\Ip::V6_ONLY_PUBLIC, '192.168.2.1', false ],
            [ VD\Ip::V6_ONLY_PUBLIC, '7708:7d89:363d:b185::', true ],
            [ VD\Ip::V6_ONLY_PUBLIC, '2001:0db8:85a3:8d3::', false ],
            [ VD\Ip::V6_ONLY_PUBLIC, 'fd9e:21a7:a92c:2323::', false ],
            [ VD\Ip::ALL_NO_PRIV, '207.142.131.235', true ],
            [ VD\Ip::ALL_NO_PRIV, '127.0.0.1', true ],
            [ VD\Ip::ALL_NO_PRIV, '192.168.2.1', false ],
            [ VD\Ip::ALL_NO_PRIV, '7708:7d89:363d:b185::', true ],
            [ VD\Ip::ALL_NO_PRIV, '2001:0db8:85a3:8d3::', true ],
            [ VD\Ip::ALL_NO_PRIV, 'fd9e:21a7:a92c:2323::', false ],
            [ VD\Ip::V4_NO_PRIV, '207.142.131.235', true ],
            [ VD\Ip::V4_NO_PRIV, '127.0.0.1', true ],
            [ VD\Ip::V4_NO_PRIV, '192.168.2.1', false ],
            [ VD\Ip::V4_NO_PRIV, '7708:7d89:363d:b185::', false ],
            [ VD\Ip::V4_NO_PRIV, '2001:0db8:85a3:8d3::', false ],
            [ VD\Ip::V4_NO_PRIV, 'fd9e:21a7:a92c:2323::', false ],
            [ VD\Ip::V6_NO_PRIV, '207.142.131.235', false ],
            [ VD\Ip::V6_NO_PRIV, '127.0.0.1', false ],
            [ VD\Ip::V6_NO_PRIV, '192.168.2.1', false ],
            [ VD\Ip::V6_NO_PRIV, '7708:7d89:363d:b185::', true ],
            [ VD\Ip::V6_NO_PRIV, '2001:0db8:85a3:8d3::', true ],
            [ VD\Ip::V6_NO_PRIV, 'fd9e:21a7:a92c:2323::', false ],
            [ VD\Ip::ALL_NO_RES, '207.142.131.235', true ],
            [ VD\Ip::ALL_NO_RES, '127.0.0.1', false ],
            [ VD\Ip::ALL_NO_RES, '192.168.2.1', true ],
            [ VD\Ip::ALL_NO_RES, '7708:7d89:363d:b185::', true ],
            [ VD\Ip::ALL_NO_RES, '2001:0db8:85a3:8d3::', false ],
            [ VD\Ip::ALL_NO_RES, 'fd9e:21a7:a92c:2323::', true ],
            [ VD\Ip::V4_NO_RES, '207.142.131.235', true ],
            [ VD\Ip::V4_NO_RES, '127.0.0.1', false ],
            [ VD\Ip::V4_NO_RES, '192.168.2.1', true ],
            [ VD\Ip::V4_NO_RES, '7708:7d89:363d:b185::', false ],
            [ VD\Ip::V4_NO_RES, '2001:0db8:85a3:8d3::', false ],
            [ VD\Ip::V4_NO_RES, 'fd9e:21a7:a92c:2323::', false ],
            [ VD\Ip::V6_NO_RES, '207.142.131.235', false ],
            [ VD\Ip::V6_NO_RES, '127.0.0.1', false ],
            [ VD\Ip::V6_NO_RES, '192.168.2.1', false ],
            [ VD\Ip::V6_NO_RES, '7708:7d89:363d:b185::', true ],
            [ VD\Ip::V6_NO_RES, '2001:0db8:85a3:8d3::', false ],
            [ VD\Ip::V6_NO_RES, 'fd9e:21a7:a92c:2323::', true ],
        ];
    }
}
