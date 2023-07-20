<?php

namespace Gobiz\Support;

use Carbon\Carbon;
use DateTimeInterface;
use DateTimeZone;
use Exception;

class DateTime extends Carbon
{
    /**
     * The date format
     */
    const DATE_FORMAT = 'Y-m-d';

    /**
     * DateTime constructor
     *
     * @param DateTimeInterface|string|null $time
     * @param DateTimeZone|string|null $tz
     */
    public function __construct($time = null, $tz = null)
    {
        ($time instanceof DateTimeInterface)
            ? parent::__construct($time->format('Y-m-d H:i:s.u'), $time->getTimezone())
            : parent::__construct($time, $tz);
    }

    /**
     * Make date time instance from specific format
     *
     * @param string $time
     * @param string $format
     * @param DateTimeZone|string|null $tz
     * @return null|static
     */
    public static function makeFromFormat($time, $format, $tz = null)
    {
        try {
            return static::createFromFormat($format, $time, $tz);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Make date time instance from the data string format
     *
     * @param string $date
     * @param DateTimeZone|string|null $tz
     * @return null|static
     */
    public static function makeFromDateFormat($date, $tz = null)
    {
        return static::makeFromFormat($date, static::DATE_FORMAT, $tz);
    }
}