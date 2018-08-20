<?php

use Dormilich\ARIN\XmlHandlerInterface;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\ReadOnly;
use Dormilich\ARIN\Transformers\ElementTransformer;
use Dormilich\ARIN\Validators\ClassList;
use Dormilich\ARIN\Validators\NamedElement;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function testGroupFromElement()
    {
        $g = new Group( 'test' );
        $e = new Element( 'phpunit' );
        $e->setValue( 'foo' );

        $this->assertCount( 0, $g );
        $this->assertFalse( $g->isValid() );

        $g->addValue( $e );

        $this->assertCount( 1, $g );
        $this->assertTrue( $g->isValid() );

        $this->assertInstanceOf( Element::class, $g[ 0 ] );
        $this->assertSame( 'phpunit', $g[ 0 ]->getName() );
        $this->assertSame( 'foo', $g[ 0 ]->getValue() );
        $this->assertSame( $e, $g[ 0 ] );

        $data = $g->getValue();

        $this->assertCount( 1, $data );
        $this->assertArrayHasKey( 0, $data );
        $this->assertSame( 'foo', $data[ 0 ] );

        $g->setValue( NULL );

        $this->assertCount( 0, $g );
    }

    public function testAddNullIsIgnored()
    {
        $g = new Group( 'test' );

        $this->assertCount( 0, $g );

        $g->addValue( NULL );

        $this->assertCount( 0, $g );
    }

    public function testNamedGroupFromString()
    {
        $setup = new Element( 'phpunit' );
        $tf = new ElementTransformer( $setup );
        $vd = new NamedElement( [ 'name' => $setup->getName() ] );

        $g = new Group( 'test' );
        $g->apply( $tf );
        $g->test( $vd );
        $g->addValue( 'foo' );

        $this->assertSame( 'phpunit', $g[ 0 ]->getName() );
        $this->assertSame( 'foo', $g[ 0 ]->getValue() );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage Value [Dormilich\ARIN\Elements\Element] is not allowed for the [test] group element.
     */
    public function testNamedGroupFromInvalidObjectFails()
    {
        $vd = new NamedElement( [ 'name' => 'phpunit' ] );
        $e = new Element( 'xxx' );
        $e->setValue( 'foo' );

        $g = new Group( 'test' );
        $g->test( $vd );
        $g->addValue( $e );
    }

    public function testGroupFromClass()
    {
        $vd = new ClassList( [ 'choices' => [ Generated::class, ReadOnly::class ] ] );

        $e = new ReadOnly( 'phpunit' );
        $e->setValue( 'foo' );

        $g = new Group( 'test' );
        $g->test( $vd );
        $g->addValue( $e );

        $this->assertCount( 1, $g );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage Value [Dormilich\ARIN\Elements\Element] is not allowed for the [test] group element.
     */
    public function testGroupFromInvalidClassFails()
    {
        $vd = new ClassList( [ 'choices' => [ Generated::class, ReadOnly::class ] ] );

        $e = new Element( 'phpunit' );
        $e->setValue( 'foo' );

        $g = new Group( 'test' );
        $g->test( $vd );
        $g->addValue( $e );
    }

    public function testIteration()
    {
        $setup = new Element( 'phpunit' );
        $tf = new ElementTransformer( $setup );

        $g = new Group( 'test' );
        $g->apply( $tf );
        $g->addValue( 'foo' );
        $g->addValue( 'bar' );

        $this->assertCount( 2, $g );
        $this->assertContainsOnlyInstancesOf( Element::class, $g );

        $iter = iterator_to_array( $g );

        $this->assertArrayHasKey( 0, $iter );
        $this->assertArrayHasKey( 1, $iter );
        $this->assertSame( 'foo', $iter[ 0 ]->getValue() );
        $this->assertSame( 'bar', $iter[ 1 ]->getValue() );
    }

    public function testArrayAccess()
    {
        $setup = new Element( 'phpunit' );
        $tf = new ElementTransformer( $setup );

        $g = new Group( 'test' );
        $g->apply( $tf );
        $g->addValue( 'foo' );
        $g->addValue( 'bar' );

        $this->assertTrue( isset( $g[ 0 ] ), 'isset' );
        $this->assertTrue( isset( $g[ -1 ] ), 'reverse isset' );

        $this->assertSame( 'foo', $g[ 0 ]->getValue(), 'get' );
        $this->assertSame( 'bar', $g[ -1 ]->getValue(), 'reverse get' );

        $this->assertCount( 2, $g );

        unset( $g[ 1 ] );

        $this->assertCount( 1, $g, 'unset' );
        $this->assertTrue( isset( $g[ 0 ] ), 'unset' );

        $g[] = 'abc';

        $this->assertSame( 'abc', $g[ 1 ]->getValue(), 'add' );

        $g[ 1 ] = 'xyz';

        $this->assertSame( 'xyz', $g[ 1 ]->getValue(), 'set' );
    }

    public function testArrayAccessWithHandle()
    {
        $a = $this->createHandleMock( 'foo' );
        $b = $this->createHandleMock( 'bar' );

        $g = new Group( 'test' );
        $g->addValue( $a );
        $g->addValue( $b );

        $this->assertTrue( isset( $g[ 'FOO' ] ), 'isset' );
        $this->assertFalse( isset( $g[ 'foo' ] ), 'not set' );
        $this->assertSame( 'foo', $g[ 'FOO' ]->getValue(), 'get' );
    }

    private function createHandleMock( $handle )
    {
        $mock = $this->getMockBuilder( XmlHandlerInterface::class )
            ->setMethods( [ 'getHandle', 'getValue', 'getName', 'isValid', 'xmlAppend', 'xmlParse' ] )
            ->getMock()
        ;
        $mock->method( 'getHandle' )->willReturn( strtoupper( $handle ) );
        $mock->method( 'getValue' )->willReturn( strtolower( $handle ) );

        return $mock;
    }

    public function testAssignIterableObject()
    {
        $setup = new Element( 'phpunit' );
        $tf = new ElementTransformer( $setup );

        $g = new Group( 'test' );
        $g->apply( $tf );

        $obj = new ArrayObject( [ 'foo', 'bar' ] );

        $g->setValue( $obj );

        $this->assertCount( 2, $g );
        $this->assertArrayHasKey( 0, $g );
        $this->assertArrayHasKey( 1, $g );
        $this->assertSame( 'foo', $g[ 0 ]->getValue() );
        $this->assertSame( 'bar', $g[ 1 ]->getValue() );
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Undefined index: 9
     */
    public function testUndefinedOffsetEmitsWarning()
    {
        $g = new Group( 'test' );
        $g[ 9 ];
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Undefined index: 9
     */
    public function testUndefinedElementOffsetEmitsWarning()
    {
        $setup = new Element( 'phpunit' );
        $tf = new ElementTransformer( $setup );

        $g = new Group( 'test' );
        $g->apply( $tf );
        // suppress warning so PHPUnit does not convert it to an exception
        $e = @$g[ 9 ];

        $this->assertInstanceOf( Element::class, $e );
        $this->assertNull( $e->getValue() );

        $g[ 9 ];
    }

    public function testElementGroupToXml()
    {
        $xml = simplexml_load_string( '<root/>' );

        $setup = new Element( 'test' );
        $tf = new ElementTransformer( $setup );

        $g = new Group( 'phpunit' );
        $g->apply( $tf );
        $g->addValue( 'foo' );
        $g->addValue( 'bar' );
        $g->xmlAppend( $xml );

        $expected = '<phpunit><test>foo</test><test>bar</test></phpunit>';
        $this->assertSame( $expected, $xml->phpunit->asXML() );
    }

    public function testEmptyGroupToXml()
    {
        $xml = simplexml_load_string( '<root/>' );

        $g = new Group( 'phpunit' );
        $g->xmlAppend( $xml );

        $this->assertSame( 0, $xml->count() );
    }

    public function testXmlToElementGroup()
    {
        $xmlStr = '<phpunit><test>foo</test><test>bar</test></phpunit>';
        $xml = simplexml_load_string( $xmlStr );

        $setup = new Element( 'test' );
        $tf = new ElementTransformer( $setup );
        $vd = new NamedElement( [ 'name' => $setup->getName() ] );
        $g = new Group( 'phpunit' );
        $g->apply( $tf );
        $g->test( $vd );
        $g->xmlParse( $xml );

        $this->assertSame( [ 'foo', 'bar' ], $g->getValue() );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ParserException
     * @expectedExceptionMessage XML element <test> is not valid for the [phpunit] group element.
     */
    public function testInvalidXmlToElementGroupFails()
    {
        $xmlStr = '<phpunit><test>foo</test><test>bar</test></phpunit>';
        $xml = simplexml_load_string( $xmlStr );
        // the default transformer just returns its input
        $g = new Group( 'phpunit' );
        $g->xmlParse( $xml );

        $this->assertSame( [ 'foo', 'bar' ], $g->getValue() );
    }
}
