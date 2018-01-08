<?php

use Dormilich\ARIN\Elements\Element;
use PHPUnit\Framework\TestCase;

class XmlTest extends TestCase
{
    public function testPlainElement()
    {
        $xml = simplexml_load_string( '<root/>' );

        $e = new Element( 'test' );
        $e->setValue( 'phpunit' );
        $e->xmlAppend( $xml );

        $this->assertSame( 'phpunit', (string) $xml->test, 'value' );
        $this->assertSame( '<test>phpunit</test>', $xml->test->asXML(), 'source' );
    }

    public function testUnprefixedNamespaceElement()
    {
        $xml = simplexml_load_string( '<root/>' );

        $e = new Element( 'test', 'http://example.org/foo' );
        $e->setValue( 'phpunit' );
        $e->xmlAppend( $xml );

        $this->assertSame( 'phpunit', (string) $xml->test, 'value' );
        $this->assertSame( '<test xmlns="http://example.org/foo">phpunit</test>', $xml->test->asXML(), 'source' );
    }

    public function testPrefixedNamespaceElement()
    {
        $xml = simplexml_load_string( '<root/>' );

        $e = new Element( 'foo:test', 'http://example.org/foo' );
        $e->setValue( 'phpunit' );
        $e->xmlAppend( $xml );

        // direct access to prefixed namespaces is ... meh
        $expected = <<<XML
<?xml version="1.0"?>
<root><foo:test xmlns:foo="http://example.org/foo">phpunit</foo:test></root>
XML;
        $this->assertSame( $expected, trim( $xml->asXML() ), 'source' );
    }

    public function testParsePlainElement()
    {
        $xml = simplexml_load_string( '<test>phpunit</test>' );

        $e = new Element( 'test' );
        $e->xmlParse( $xml );

        $this->assertTrue( $e->isDefined(), 'defined' );
        $this->assertTrue( $e->isValid(), 'valid' );
        $this->assertSame( 'phpunit', $e->getValue(), 'value' );
    }

    public function testParseElementWithAttribute()
    {
        $xml = simplexml_load_string( '<test name="foo">phpunit</test>' );

        $e = new Element( 'test' );
        $e->xmlParse( $xml );

        $this->assertTrue( $e->isDefined(), 'defined' );
        $this->assertTrue( $e->isValid(), 'valid' );
        $this->assertSame( 'phpunit', $e->getValue(), 'value' );
        $this->assertSame( 'foo', $e->name, 'attribute' );
    }
}
