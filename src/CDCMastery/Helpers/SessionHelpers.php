<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 1:31 PM
 */

namespace CDCMastery\Helpers;


class SessionHelpers
{
    const KEY_AUTH = 'cdcmastery_auth';

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION[self::KEY_AUTH]);
    }
}