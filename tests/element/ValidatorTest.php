<?php

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Validators as VD;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testChoice()
    {
        $e = new Element( 'choice' );
        $e->test( new VD\Choice( [ 'choices' => [ 'foo', 'bar' ] ] ) );
        $e->setValue( 'foo' );

        $this->assertTrue( $e->isValid() );
    }

    public function testDatetime()
    {
        $e = new Element( 'datetime' );
        $e->test( new VD\Datetime( [ 'format' => 'Y-m-d' ] ) );
        $e->setValue( '1997-04-18' );

        $this->assertTrue( $e->isValid() );
    }

    public function testEmail()
    {
        $e = new Element( 'email' );
        $e->test( new VD\Email );
        $e->setValue( 'me@example.org' );

        $this->assertTrue( $e->isValid() );
    }

    public function testIp()
    {
        $e = new Element( 'ip' );
        $e->test( new VD\Ip( [ 'version' => '4' ] ) );
        $e->setValue( '192.168.2.1' );

        $this->assertTrue( $e->isValid() );
    }

    public function testRange()
    {
        $e = new Element( 'range' );
        $e->test( new VD\Range( [ 'min' => 5, 'max' => 17 ] ) );
        $e->setValue( '12' );

        $this->assertTrue( $e->isValid() );
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
        $e = new Element( 'regexp' );
        $e->test( new VD\RegExp( [ 'pattern' => '/^AS[1-9]\d*$/' ] ) );
        $e->setValue( 'AS123' );

        $this->assertTrue( $e->isValid() );
    }

    public function testUnsigned()
    {
        $e = new Element( 'unsigned' );
        $e->test( 'ctype_digit' );
        $e->setValue( 42 );

        $this->assertTrue( $e->isValid() );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage Value ['foo'] is not allowed for the [invalid] element.
     */
    public function testInvalidInputFails()
    {
        $e = new Element( 'invalid' );
        $e->test( 'ctype_digit' );
        $e->setValue( 'foo' );
    }
}
