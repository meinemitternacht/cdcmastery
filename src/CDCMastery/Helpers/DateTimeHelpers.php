<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 8:20 PM
 */

namespace CDCMastery\Helpers;


use DateTime;

class DateTimeHelpers
{
    public const D_FMT_SHORT = 'Y-m-d';

    public const DT_FMT_DB = 'Y-m-d H:i:s';
    public const DT_FMT_DB_DAY_START = 'Y-m-d 00:00:00';
    public const DT_FMT_DB_DAY_END = 'Y-m-d 23:59:59';

    public const DT_FMT_LONG = 'l, F jS, Y g:i:s A';
    public const DT_FMT_SHORT = 'd-M-y H:i:s';

    public static function xDaysAgo(int $days): DateTime
    {
        $dt = new DateTime();

        if ($days <= 0) {
            return $dt;
        }

        $dt->modify("-{$days} days");

        return $dt;
    }
}