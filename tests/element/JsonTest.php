<?php

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\MultiLine;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testEmptyElement()
    {
        $e = new Element( 'test' );

        $exp = json_encode( NULL );
        $act = json_encode( $e );
        $this->assertFalse( $e->isValid() );
        $this->assertJsonStringEqualsJsonString( $exp, $act );
    }

    public function testValidElement()
    {
        $e = new Element( 'test' );
        $e->setValue( 'phpunit' );

        $exp = json_encode( 'phpunit' );
        $act = json_encode( $e );
        $this->assertTrue( $e->isValid() );
        $this->assertJsonStringEqualsJsonString( $exp, $act );
    }

    public function testEmptyMultiline()
    {
        $m = new MultiLine( 'test' );

        $exp = json_encode( [] );
        $act = json_encode( $m );
        $this->assertFalse( $m->isValid() );
        $this->assertJsonStringEqualsJsonString( $exp, $act );
    }

    public function testValidMultiline()
    {
        $m = new MultiLine( 'test' );
        $m->addValue( 'foo' );
        $m->addValue( 'bar' );

        $exp = json_encode( [ 'foo', 'bar' ] );
        $act = json_encode( $m );
        $this->assertTrue( $m->isValid() );
        $this->assertJsonStringEqualsJsonString( $exp, $act );
    }

    public function testEmptyGroup()
    {
        $g = new Group( 'test' );

        $exp = json_encode( [] );
        $act = json_encode( $g );
        $this->assertFalse( $g->isValid() );
        $this->assertJsonStringEqualsJsonString( $exp, $act );
    }

    public function testValidGroup()
    {
        $foo = new Element( 'x' );
        $foo->setValue( 'foo' );
        $bar = new Element( 'x' );
        $bar->setValue( 'bar' );

        $g = new Group( 'test' );
        $g->addValue( $foo );
        $g->addValue( $bar );

        $exp = json_encode( [ 'foo', 'bar' ] );
        $act = json_encode( $g );
        $this->assertTrue( $g->isValid() );
        $this->assertJsonStringEqualsJsonString( $exp, $act );
    }
}
