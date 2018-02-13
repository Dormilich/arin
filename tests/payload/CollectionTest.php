<?php

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Collection;
use Dormilich\ARIN\Payloads\Phone;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    private $file = __DIR__ . '/_fixtures/collection.';

    public function testReadCollection()
    {
        $xml = file_get_contents( $this->file . 'xml' );
        $c = Payload::fromXML( $xml );

        $this->assertContainsOnlyInstancesOf( Phone::class, $c );
        $this->assertCount( 2, $c );
        $this->assertTrue( $c[ 0 ]->isValid() );
    }
}
