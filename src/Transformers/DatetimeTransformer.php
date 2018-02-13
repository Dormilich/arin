<?php
// DatetimeTransformer.php

namespace Dormilich\ARIN\Transformers;

use Dormilich\ARIN\Exceptions\DataTransformationException;

/**
 * Convert dates based upon a `DateTimeInterface` implementation. 
 * The transformation converts the timestamp to UTC (?), the reverse transformation 
 * converts it back to the configured timezone.
 * 
 * If a custom date object is used, NULL values must be treated accordingly.
 */
class DatetimeTransformer implements DataTransformerInterface
{
    /**
     * @var DateTimeZone
     */
    protected $xmlTimezone = 'UTC'; // America/New_York ?

    /**
     * @var DateTimeZone
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $dateClass = 'DateTimeImmutable';

    /**
     * @var string XML date format
     */
    protected $format = DATE_RFC3339;

    public function __construct( \DateTimeInterface $date = NULL, $format = DATE_RFC3339 )
    {
        if ( $date ) {
            $this->dateClass = get_class( $date );
        }
        $this->format = (string) $format;
        $this->timezone = $this->getTimezone( $date );
        $this->xmlTimezone = new \DateTimeZone( $this->xmlTimezone );
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
        $value = $this->setTimezone( $value, $this->xmlTimezone );

        if ( $value instanceof \DateTimeInterface ) {
            return $value->format( $this->format );
        }

        try {
            // read date in current timezone and convert to UTC
            $date = $this->createDate( $value, $this->timezone );
            $date->setTimezone( $this->xmlTimezone );
            return $date->format( $this->format );
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

        $value = $this->createDate( $value, $this->xmlTimezone )->format( 'c' );
        $date = new $class( $value, $this->xmlTimezone );
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

    /**
     * Create a date object first trying the expected format before using the 
     * value as is.
     * 
     * @param mixed $date Date string.
     * @param DateTimeZone $tz 
     * @return DateTime
     * @throws Exception Invalid argument for DateTime constructor.
     */
    private function createDate( $date, \DateTimeZone $tz )
    {
        return date_create_from_format( $this->format, $date, $tz ) ?: new \DateTime( $date, $tz );
    }
}
