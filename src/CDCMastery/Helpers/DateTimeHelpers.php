<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 8:20 PM
 */

namespace CDCMastery\Helpers;


class DateTimeHelpers
{
    const D_FMT_SHORT = 'Y-m-d';

    const DT_FMT_DB = 'Y-m-d H:i:s';
    const DT_FMT_DB_DAY_START = 'Y-m-d 00:00:00';
    const DT_FMT_DB_DAY_END = 'Y-m-d 23:59:59';

    const DT_FMT_LONG = 'l, F jS, Y g:i:s A';
    const DT_FMT_SHORT = 'd-M-y H:i:s';

    /** 4 years max (365.25 * 4) */
    const MAX_DAYS_AGO = 1461;

    public static function xDaysAgo(int $days): \DateTime
    {
        $dt = new \DateTime();

        if ($days <= 0) {
            return $dt;
        }

        if ($days > self::MAX_DAYS_AGO) {
            $days = self::MAX_DAYS_AGO;
        }

        $dt->modify("-{$days} days");

        return $dt;
    }
}