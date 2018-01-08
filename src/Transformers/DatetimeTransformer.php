<?php
// DatetimeTransformer.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Exceptions\DataTransformationException;

/**
 * Use functions for transformation. 
 */
class DatetimeTransformer implements DataTransformerInterface
{
    /**
     * @var DateTimeZone
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $dateClass = 'DateTimeImmutable';

    public function __construct( \DateTimeInterface $date = NULL )
    {
        $this->timezone = $this->getTimezone( $date );

        if ( $date ) {
            $this->dateClass = get_class( $date );
        }
    }

    /**
     * Determine the timezone to be used. Defaults to the system setting.
     * 
     * @param DateTimeInterface|null $date 
     * @return DateTimeZone
     */
    private function getTimezone( \DateTimeInterface $date = NULL )
    {
        if ( $date ) {
            return $date->getTimezone();
        }

        return new \DateTimeZone( date_default_timezone_get() );
    }

    /**
     * Reads a datetime instance and converts it into ISO format.
     * 
     * @param mixed $value Input data.
     * @return mixed XML data.
     */
    public function transform( $value )
    {
        if ( method_exists( $value, 'setTimezone' ) ) {
            $value->setTimezone( $this->timezone );
        }

        if ( $value instanceof \DateTimeInterface ) {
            return $value->format( 'c' );
        }

        try {
            return date_create( $value, $this->timezone )->format( 'c' );
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Creates a new date instance of the value. NULL values are treated as 
     * dates as well, except for the native datetime classes.
     * 
     * @param string|NULL $value XML data.
     * @return DateTimeInterface Output data.
     */
    public function reverseTransform( $value )
    {
        $class = $this->dateClass;

        if ( NULL === $value and in_array( $class, [ 'DateTime', 'DateTimeImmutable' ], true ) ) {
            return $value;
        }

        return new $class( $value, $this->timezone );
    }
}
