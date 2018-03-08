<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Exceptions\ValidationException;
use Dormilich\ARIN\Transformers\CallbackTransformer;
use Dormilich\ARIN\Validators\Base64;

/**
 * This payload allows you to add attachments to an existing Ticket as part of 
 * an Add Message call. 
 * 
 * This Attachment Payload should not be submitted by itself.
 */
class Attachment extends Payload
{
    /**
     * @inheritDoc
     */
    protected $name = 'attachment';

    /**
     * @param string|SplFileInfo $file 
     * @return self
     */
    public function __construct( $file = NULL )
    {
        $this->init();

        if ( is_string( $file ) ) {
            $file = new \SplFileInfo( $file );
        }
        if ( $file and $file->isFile() ) {
            $this->set( 'filename', $file->getBasename() );
            $data = $this->readFile( $file );
            $this->set( 'data', $data );
        }
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->define( NULL, new Element( 'data' ) )
            ->apply( new CallbackTransformer( 'strval', 'base64_decode' ) )
            ->test( new Base64 );

        $this->define( NULL, new Element( 'filename' ) );
    }

    /**
     * Read file content and convert in to base64.
     * 
     * @param SplFileInfo $info 
     * @return string
     * @throws ValidationException
     */
    private function readFile( \SplFileInfo $info )
    {
        try {
            $file = $info->openFile();
            $file->rewind();
            $size = $file->getSize();
            $data = $file->fread( $size );
            return base64_encode( $data );
        } catch ( \Exception $e ) {
            $msg = 'File [%s] could not be loaded in the Attachment Payload.';
            throw new ValidationException( sprintf( $msg, $info->getBasename() ) );
        }
    }
}
