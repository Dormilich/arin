<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Payloads\Component;
use Dormilich\ARIN\Transformers\ElementTransformer;
use Dormilich\ARIN\Validators\ClassList;

/**
 * The Error Payload is returned when any call encounters errors and it 
 * contains the reason for the error.  
 * 
 * Error codes:
 *  - E_SCHEMA_VALIDATION   The XML data you provided did not pass the RelaxNG schema validation. 
 *  - E_ENTITY_VALIDATION   This database object failed to pass ARIN's validation
 *  - E_OBJECT_NOT_FOUND    The database object you specified was not found in our database.
 *  - E_AUTHENTICATION      The API key specified in your URL either does not exist, 
 *                          or is not associated with/authoritative over the object specified in your URL/payload.
 *  - E_NOT_REMOVEABLE      The database object you specified was not able to be 
 *                          removed due to current associations/links to other objects.
 *  - E_BAD_REQUEST         The request you made was invalid. [...] the error message will provide details for the fix.
 *  - E_OUTAGE              The Reg-RWS server is currently undergoing maintenance and is not available.
 *  - E_UNSPECIFIED         A universal error code for unspecified errors.
 */
class Error extends Payload
{
    // @see https://www.arin.net/resources/restfulmethods.html#errorcodes
    const E_UNSPECIFIED = 0;
    const E_SCHEMA_VALIDATION = 1;
    const E_ENTITY_VALIDATION = 2;
    const E_BAD_REQUEST = 400;
    const E_AUTHENTICATION = 403;
    const E_OBJECT_NOT_FOUND = 404;
    const E_NOT_REMOVEABLE = 409;
    const E_OUTAGE = 503;

    /**
     * @inheritDoc
     */
    protected $name = 'error';

    /**
     * Return the code & message.
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->get( 'code' ) . ': ' . $this->get( 'message' );
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $message = new Element( 'message' );

        $this->define( NULL, new Element( 'message' ) );

        $this->define( NULL, new Element( 'code' ) );

        $this->define( NULL, new Group( 'components' ) )
            ->test( new ClassList( [ 'choices' => Component::class ] ) );
        // this doesn't seem to be a group, but it's the only way to define the 
        // structure without an explicit payload
        $this->define( 'info', new Group( 'additionalInfo' ) )
            ->apply( new ElementTransformer( $message ) )
            // to prevent confusion with the Message payload
            ->test( new ClassList( [ 'choices' => Element::class ] ) );
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        return false;
    }

    /**
     * Get the textual error code as integer for use in exceptions.
     * 
     * @return integer
     */
    public function getCode()
    {
        return constant( self::class . '::' . $this->get( 'code' ) );
    }

    /**
     * Get the error message for use in exceptions.
     * 
     * @return string
     */
    public function getMessage()
    {
        return $this->get( 'message' );
    }
}
