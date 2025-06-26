<?php

namespace Nicepay\NicePayment\Helper;

use DateTime;
use DateTimeZone;

class DateHelper
{


    public static function getFormattedDate($date = null)
    {
        if ($date === null) {
            $date = new DateTime();
        }

        $date->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $date->format('Y-m-d\TH:i:sP');
    }


    public static function getFormattedTimestampV2($date = null)
    {
        if ($date === null) {
            $date = new DateTime();
        }
        $date->setTimezone(new DateTimeZone('Asia/Jakarta')); // Set the timezone to 'Asia/Jakarta'
        return $date->format('YmdHis');
    }

    public static function getFormattedDateCustom($date = null, $format = 'Y-m-d\TH:i:sP')
    {
        if ($date === null) {
            $date = new DateTime();
        }

        $date->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return $date->format($format);
    }

    public static function convertTimestampToDateTime(string $timestamp): \DateTime
    {
        $date = \DateTime::createFromFormat('YmdHis', $timestamp, new \DateTimeZone('Asia/Jakarta'));

        if (!$date) {
            throw new \InvalidArgumentException("Invalid timestamp format: $timestamp");
        }

        return $date;
    }

    public static function formatTimestampToStringDateTime($timestamp)
    {
        // Parse the string according to 'YmdHis' format with Jakarta timezone
        $date = DateTime::createFromFormat('YmdHis', $timestamp, new DateTimeZone('Asia/Jakarta'));

        if (!$date) {
            throw new \InvalidArgumentException("Invalid timestamp format: $timestamp");
        }

        // Return formatted string in your desired format
        return $date->format('d-m-Y H:i:s');
    }
}
