<?php

namespace Gobiz\Support;

class Conversion implements ConversionInterface
{

    /**
     * Convert can nang, theo don vi kg.
     * Do chinh xac mac dinh la 10gram
     *
     * @param float $weight
     * @param int $places
     * @return float
     */
    public static function convertWeight(float $weight, $places = 3)
    {
        return round((double)$weight, $places);
    }

    /**
     * Convert the tich, theo don vi m3
     * Do chinh xac den 0.01 m3
     *
     * @param float $volume
     * @param int $places
     * @return float
     */
    public static function convertVolume(float $volume, $places = 9)
    {
        return round((double)$volume, $places);
    }

    /**
     * Convert do dai, theo don vi m
     * Do chinh xac den 1cm
     *
     * @param float $height
     * @param int $places
     * @return float
     */
    public static function convertHeight(float $height, $places = 3)
    {
        return round((double)$height, $places);
    }

    /**
     * Convert time thanh dang datetime chuan su dung
     * trong db
     *
     * @param $time
     * @param bool $onlyDate
     * @return false|string
     */
    public static function convertDatetime($time, $onlyDate = false)
    {
        if ($onlyDate) {
            return date('Y-m-d', $time);
        }
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * Convert so tien, theo don vi VND
     *
     * @param float $money
     * @param int $places
     * @return float
     */
    public static function convertMoney(float $money, $places = 3)
    {
        return round((double)$money, $places);
    }

    /**
     * Convert dashes string to camel case
     *
     * @param $string
     * @param bool $capitalizeFirstCharacter
     * @return string
     */
    public static function dashesToCamelCase($string, $capitalizeFirstCharacter = true)
    {

        $str = ucwords(str_replace(['-', '_'], ' ', $string));
        $str = str_replace(' ', '', $str);

        if ($capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }

        return $str;
    }
}