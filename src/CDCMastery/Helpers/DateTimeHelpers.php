<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 8:20 PM
 */

namespace CDCMastery\Helpers;


use DateTime;
use DateTimeZone;

class DateTimeHelpers
{
    public const D_FMT_SHORT = 'Y-m-d';

    public const DT_FMT_DB = 'Y-m-d H:i:s';
    public const DT_FMT_DB_DAY_START = 'Y-m-d 00:00:00';
    public const DT_FMT_DB_DAY_END = 'Y-m-d 23:59:59';

    public const DT_FMT_LONG = 'l, F jS, Y g:i:s A';
    public const DT_FMT_SHORT = 'd-M-y H:i:s';

    private static DateTimeZone $utc_tz;

    public static function x_days_ago(int $days): DateTime
    {
        $dt = new DateTime();

        if ($days <= 0) {
            return $dt;
        }

        $dt->modify("-{$days} days");

        return $dt;
    }

    public static function list_time_zones(bool $keyed = true): array
    {
        $regions = [
            'Africa' => DateTimeZone::AFRICA,
            'America' => DateTimeZone::AMERICA,
            'Antarctica' => DateTimeZone::ANTARCTICA,
            'Asia' => DateTimeZone::ASIA,
            'Atlantic' => DateTimeZone::ATLANTIC,
            'Europe' => DateTimeZone::EUROPE,
            'Indian' => DateTimeZone::INDIAN,
            'Pacific' => DateTimeZone::PACIFIC,
        ];

        $tzlist = [];
        foreach ($regions as $name => $mask) {
            if (!$keyed) {
                $tzlist[] = DateTimeZone::listIdentifiers($mask);
                continue;
            }

            $tzlist[ $name ] = DateTimeZone::listIdentifiers($mask);
        }

        return $tzlist;
    }

    public static function utc_tz(): DateTimeZone
    {
        if (!self::$utc_tz) {
            self::$utc_tz = new DateTimeZone('UTC');
        }

        return self::$utc_tz;
    }
}