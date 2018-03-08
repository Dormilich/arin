<?php
// NetBlock.php

namespace Dormilich\ARIN\Payloads;

use Dormilich\ARIN\Elements\Payload;
use Dormilich\ARIN\Elements\ReadOnly;
use Dormilich\ARIN\Validators\Choice;
use Dormilich\ARIN\Transformers\IntegerTransformer;
use Dormilich\ARIN\Transformers\IpTransformer;
use Dormilich\ARIN\Validators\Ip;
use Dormilich\ARIN\Validators\Range;
use Dormilich\Http\NetworkInterface;

/**
 * The NET Block Payload contains details on the NET Block of the Network 
 * specified. The NET Block Payload is a nested element of a NET Payload. 
 * See NET Payload for additional details.
 * 
 * When submitting a NET Block Payload as part of the NET Payload, the IP 
 * addresses provided in the startAddress and endAddress elements can be 
 * non-zero-padded (i.e. 10.0.0.255) or zero-padded (i.e. 010.000.000.255). 
 * The payload returned will always express IP addresses as zero-padded.
 * 
 * The description field will be determined by the type you specify, and may 
 * be left blank.
 * 
 * Net types:
 *      A  - Reallocation
 *      S  - Reassignment
 *      DA - Direct Allocation
 *      DS - Direct Assignment
 *  ARIN
 *      AR - allocated
 *      AV - early reservation
 *  AFRINIC
 *      AF - allocated
 *      FX - transferred
 *  APNIC
 *      AP - allocated
 *      PX - early registration
 *      PV - early reservation
 *  IANA
 *      IR - reserved
 *      IU - special use
 *  LACNIC
 *      LN - allocated
 *      LX - transferred
 *  RIPE
 *      RN - allocated
 *      RV - early reservation
 *  RIPE NCC
 *      RD - allocated
 *      RX - transferred
 */
class NetBlock extends Payload
{
    /**
     * @inheritDoc
     */
    protected $name = 'netBlock';

    /**
     * @param NetworkInterface|NULL $net A network object
     * @return self
     */
    public function __construct( NetworkInterface $net = NULL )
    {
        $this->init();

        if ( $net ) {
            $this->set( 'start', $net->getNetwork() );
            $this->set( 'length', $net->getPrefixLength() );
        }
    }

    /**
     * Return a representation of the addressed space. If a CIDR can't be built, 
     * display as IP range.
     * 
     * @return string
     */
    public function __toString()
    {
        if ( ! $this->isValid() ) {
            return '';
        }

        $end = $this->get( 'end' );
        $start = $this->get( 'start' );
        $length = $this->get( 'length' );

        return $start . ( $length ? '/' . $length : ' - ' . $end );
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $ip  = new IpTransformer;
        $public = new IP( [ 'version' => Ip::ALL_ONLY_PUBLIC ] );
        $abbr = [
            'A',  'AF', 'AP', 'AR', 'AV', 'DA', 'DS', 'FX', 'IR', 'IU', 
            'LN', 'LX', 'PV', 'PX', 'RD', 'RN', 'RV', 'RX', 'S', 
        ];
        $this->define( NULL, new ReadOnly( 'type' ) )
            ->test( new Choice( [ 'choices' => $abbr ] ) );

        $this->define( NULL, new ReadOnly( 'description' ) );

        $this->define( 'start', new ReadOnly( 'startAddress' ) )
            ->apply( $ip )
            ->test( $public );

        $this->define( 'end', new ReadOnly( 'endAddress' ) )
            ->apply( $ip )
            ->test( $public );

        $this->define( 'length', new ReadOnly( 'cidrLength', 0, 128))
            ->apply( new IntegerTransformer )
            ->test( new Range( [ 'min' => 0, 'max' => 128 ] ) );
    }

    /**
     * @inheritDoc
     */
    public function isValid()
    {
        $valid = $this->validity();

        return $valid[ 'type' ] and $valid[ 'start' ] and ( $valid[ 'end' ] or $valid[ 'length' ] );
    }
}
