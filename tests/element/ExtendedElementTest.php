<?php

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\ReadOnly;
use Dormilich\ARIN\Transformers as TF;
use Dormilich\ARIN\Validators as VD;
use PHPUnit\Framework\TestCase;

class ExtendedElementTest extends TestCase
{
    // for lack of a better place ...
    public function testElementString()
    {
        $e = new Element( 'test' );
        $e->setValue( 'foo' );

        $this->assertSame( 'foo', (string) $e );
    }

    public function testReadonlyElement()
    {
        $e = new ReadOnly( 'read' );
        $e->setValue( true );

        $this->assertSame( 'true', $e->getValue(), 'first value' );

        $e->setValue( $e->getValue() );

        $this->assertSame( 'true', $e->getValue(), 'second value' );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage The [read] element must not be modified once it is set.
     */
    public function testReadonlyModifyFails()
    {
        $e = new ReadOnly( 'read' );
        $e->setValue( true );

        $this->assertSame( 'true', $e->getValue() );

        $e->setValue( false );
    }

    /**
     * @expectedException Dormilich\ARIN\Exceptions\ValidationException
     * @expectedExceptionMessage The [auto] element must not be modified once it is set.
     */
    public function testGeneratedModifyFails()
    {
        $e = new Generated( 'auto' );
        // since this comes off the API, the values are always strings
        $e->setValue( 'true' );

        $this->assertSame( 'true', $e->getValue() );

        $e->setValue( 'false' );
    }

    public function testGeneratedSkipsTransformer()
    {
        $e = new Generated( 'auto' );
        $e->apply( new TF\BooleanTransformer ); // yes => true
        $e->setValue( 'yes' );

        $this->assertSame( 'yes', $e->getValue() );
    }

    public function testGeneratedRunsReverseTransformer()
    {
        $e = new Generated( 'auto' );
        $e->apply( new TF\BooleanTransformer ); // 'true' => TRUE
        $e->setValue( 'true' );

        $this->assertSame( true, $e->getValue() );
    }

    public function testGeneratedSkipsValidator()
    {
        $e = new Generated( 'auto' );
        $e->test( new VD\Email );
        $e->setValue( 'foo' );

        $this->assertSame( 'foo', $e->getValue() );
    }

    public function testGeneratedResetsOnClone()
    {
        $xml = simplexml_load_string( '<auto type="generated">phpunit</auto>' );

        $e = new Generated( 'auto' );
        $e->xmlParse( $xml );

        $this->assertTrue( $e->isDefined(), 'original defined' );
        $this->assertSame( 'phpunit', $e->getValue(), 'original value' );
        $this->assertSame( 'generated', $e->type, 'original attribute' );

        $c = clone $e;

        $this->assertFalse( $c->isDefined(), 'clone defined' );
        $this->assertNull( $c->getValue(), 'clone value' );
        $this->assertNull( $c->type, 'clone attribute' );
    }
}
