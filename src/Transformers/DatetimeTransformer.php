<?php
// DatetimeTransformer.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Exceptions\DataTransformationException;

/**
 * Convert dates based upon a `DateTimeInterface` implementation. 
 * The transformation converts the timestamp to UTC, the reverse transformation 
 * converts it back to the configured timezone.
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
     * Reads a datetime instance and converts it into ISO format using the UTC 
     * timezone.
     * 
     * @param mixed $value Input data.
     * @return mixed XML data.
     */
    public function transform( $value )
    {
        $utc = new \DateTimeZone( 'UTC' );
 
        $value = $this->setTimezone( $value, $utc );

        if ( $value instanceof \DateTimeInterface ) {
            return $value->format( 'c' );
        }

        try {
            // read date in current timezone and convert to UTC
            $date = new \DateTime( $value, $this->timezone );
            $date->setTimezone( $utc );
            return $date->format( 'c' );
        } catch ( \Exception $e ) {
            return $value;
        }
    }

    /**
     * Creates a new date instance of the value. NULL values are treated as 
     * dates as well, except for the native datetime classes. 
     * Uses the configured timezone, if possible.
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

        $utc = new \DateTimeZone( 'UTC' );
        $date = new $class( $value, $utc );
        $date = $this->setTimezone( $date, $this->timezone );

        return $date;
    }

    /**
     * Set the timezone on a date object, if possible (the `setTimezone()` 
     * method is not part of the `DateTimeInterface`).
     * 
     * @param mixed $date 
     * @param DateTimeZone $tz 
     * @return mixed
     */
    private function setTimezone( $date, \DateTimeZone $tz )
    {
        if ( method_exists( $date, 'setTimezone' ) ) {
            // use updated object from DateTimeImmutable
            // use original object if there is no return value
            $date = $date->setTimezone( $tz ) ?: $date;
        }

        return $date;
    }
}
