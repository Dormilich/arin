<?php

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\MultiLine;
use PHPUnit\Framework\TestCase;

class MultiLineElementTest extends TestCase
{
    public function testSetTextBlock()
    {
        $ml = new MultiLine( 'comment' );

        $this->assertFalse( $ml->isValid() );
        $this->assertFalse( $ml->isDefined() );
        $this->assertCount( 0, $ml );

        $comment = file_get_contents(__DIR__ . '/_fixtures/painting.txt');

        $ml->setValue( $comment );

        $this->assertTrue( $ml->isValid() );
        $this->assertTrue( $ml->isDefined() );
        $this->assertCount( 5, $ml );

        $this->assertSame( $comment, (string) $ml );

        $lines = explode( PHP_EOL, $comment );

        $this->assertSame( $lines, $ml->getValue() );
    }

    public function testSetTextArray()
    {
        $ml = new MultiLine( 'comment' );

        $comment = file_get_contents(__DIR__ . '/_fixtures/painting.txt');
        $lines = explode( PHP_EOL, $comment );

        $ml->setValue( $lines );

        $this->assertSame( $comment, (string) $ml );
    }

    public function testAssignIterableObject()
    {
        $ml = new MultiLine( 'comment' );

        $comment = file_get_contents(__DIR__ . '/_fixtures/painting.txt');
        $lines = explode( PHP_EOL, $comment );

        $obj = new \ArrayObject( $lines );

        $ml->setValue( $obj );

        $this->assertSame( $comment, (string) $ml );
    }

    public function testAddLines()
    {
        $ml = new MultiLine( 'test' );

        $bar = $this->createMock( 'Exception' );
        $bar->method( '__toString' )->willReturn( 'bar' );

        $this->assertCount( 0, $ml );

        $ml->addValue( 'foo' );
        $ml->addValue( $bar );

        $this->assertCount( 2, $ml );
        $this->assertSame( [ 'foo', 'bar' ], $ml->getValue() );
    }

    public function testUnsetMultiline()
    {
        $ml = new MultiLine( 'test' );

        $this->assertCount( 0, $ml );

        $ml->addValue( 'foo' );
        $ml->addValue( 'bar' );

        $this->assertCount( 2, $ml );

        $ml->setValue( NULL );

        $this->assertCount( 0, $ml );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage Value [false] is not allowed for the multi-line [test] element.
     */
    public function testMultilineWithBooleanFails()
    {
        $ml = new MultiLine( 'test' );
        $ml->setValue( false );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage Value [stdClass] is not allowed for the multi-line [test] element.
     */
    public function testMultilineWithArbitraryObjectFails()
    {
        $ml = new MultiLine( 'test' );
        $ml->setValue( new stdClass );
    }

    public function testIteration()
    {
        $ml = new MultiLine( 'test' );
        $ml->addValue( 'foo' );
        $ml->addValue( 'bar' );

        $this->assertCount( 2, $ml );
        $this->assertContainsOnlyInstancesOf( Element::class, $ml );

        $iter = iterator_to_array( $ml );

        $this->assertArrayHasKey( 0, $iter );
        $this->assertArrayHasKey( 1, $iter );
    }

    public function testArrayAccess()
    {
        $ml = new MultiLine( 'test' );
        $ml->addValue( 'foo' );
        $ml->addValue( 'bar' );

        $this->assertTrue( isset( $ml[ 0 ] ), 'isset' );
        $this->assertFalse( isset( $ml[ -1 ] ), 'no reverse isset' );

        $this->assertSame( 'foo', $ml[ 0 ]->getValue(), 'get' );
        $this->assertSame( 'bar', $ml[ -1 ]->getValue(), 'reverse get' );
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Undefined index: 9
     */
    public function testUndefinedOffsetEmitsWarning()
    {
        $ml = new MultiLine( 'test' );
        // suppress warning so PHPUnit does not convert it to an exception
        $e = @$ml[ 9 ];

        $this->assertInstanceOf( Element::class, $e );
        $this->assertFalse( $e->isDefined() );
        $this->assertNull( $e->getValue() );

        $e = $ml[ 9 ];
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage A line inside a text block may not be deleted.
     */
    public function testUnsetLineFails()
    {
        $ml = new MultiLine( 'test' );
        $ml->addValue( 'foo' );

        // a non-existent line is ignored
        unset( $ml[ 42 ] );

        $this->assertFalse( isset( $ml[ 42 ] ) );

        unset( $ml[ 0 ] );
    }

    public function testAddLineByArrayAccess()
    {
        $ml = new MultiLine( 'test' );
        $ml[] = 'foo';

        $this->assertSame( [ 'foo' ], $ml->getValue() );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage A line inside a text block may not be modified.
     */
    public function testSetLineByArrayAccessFails()
    {
        $ml = new MultiLine( 'test' );
        $ml->addValue( 'foo' );

        $ml[ 0 ] = 'bar';
    }

    public function testMultilineToXml()
    {
        $xml = simplexml_load_string( '<root/>' );

        $ml = new MultiLine( 'test' );
        $ml->addValue( 'foo' );
        $ml->addValue( 'bar' );
        $ml->xmlAppend( $xml );

        $expected = '<test><line number="1">foo</line><line number="2">bar</line></test>';
        $this->assertSame( $expected, $xml->test->asXML() );
    }

    public function testEmptyMultilineToXml()
    {
        $xml = simplexml_load_string( '<root/>' );

        $ml = new MultiLine( 'test' );
        $ml->xmlAppend( $xml );

        $this->assertSame( 0, $xml->count() );
    }

    public function testXmlToMultiline()
    {
        $xmlStr = '<test><line number="1">foo</line><line number="2">bar</line></test>';
        $xml = simplexml_load_string( $xmlStr );

        $ml = new MultiLine( 'test' );
        $ml->xmlParse( $xml );

        $this->assertSame( [ 'foo', 'bar' ], $ml->getValue() );
    }
}
