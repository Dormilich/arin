<?php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Primary;
use Dormilich\ARIN\Elements\Element;
use Dormilich\ARIN\Elements\Generated;
use Dormilich\ARIN\Elements\Group;
use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Transformers\ElementTransformer;
use Dormilich\ARIN\Validators\ClassList;
use Dormilich\ARIN\Validators\NamedElement;

/**
 * The Delegation Payload allows you to define the details of a Delegation, 
 * including nameservers and Delegation Signer (DS) keys.
 * 
 * The name field is automatically generated after you submit the payload, 
 * and should be left blank.
 */
class Delegation extends Payload implements Primary
{
    protected $name = 'delegation';

    public function __construct( $handle = NULL )
    {
        $this->init();
        $this->set( 'name', $handle );
    }

    public function __toString()
    {
        return (string) $this->getHandle();
    }

    protected function init()
    {
        $nameserver = new Element( 'nameserver' );

        $this->define( NULL, new Generated( 'name' ) );

        $this->define( 'key', new Group( 'delegationKeys' ) )
            ->test( new ClassList( [ 'choices' => DelegationKey::class ] ) );

        $this->define( NULL, new Group( 'nameservers' ) )
            ->apply( new ElementTransformer( $nameserver ) )
            ->test( new NamedElement( [ 'name' => 'nameserver' ] ) );
    }

    public function getHandle()
    {
        return $this->attr( 'name' )->jsonSerialize();
    }

    public function isValid()
    {
        return $this->attr( 'nameservers' )->isValid();
    }

    public function xmlSerialize( $encoding = 'UTF-8' )
    {
        if ( ! $this->isValid() ) {
            $msg = 'Delegation Payload %s is not valid for submission.';
            $msg = sprintf( $msg, var_export( $this->getHandle(), true ) ); 
            trigger_error( $msg, E_USER_WARNING );
        }

        $root = $this->xmlCreate( $encoding );
        return $this->xmlAppend( $root );
    }
}