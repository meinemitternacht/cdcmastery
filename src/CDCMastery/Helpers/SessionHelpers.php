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
    const CLASS_ADMIN = 'admin';
    const CLASS_SUPERVISOR = 'supervisor';
    const CLASS_TRNG_MGR = 'training_manager';

    const KEY_AUTH = 'cdcmastery_auth';
    const KEY_CLASS = 'user_class';

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION[self::KEY_AUTH]);
    }

    public static function isAdmin(): bool
    {
        if (!isset($_SESSION[self::KEY_CLASS])) {
            return false;
        }

        return $_SESSION[self::KEY_CLASS] === self::CLASS_ADMIN;
    }

    public static function isSupervisor(): bool
    {
        if (!isset($_SESSION[self::KEY_CLASS])) {
            return false;
        }

        return $_SESSION[self::KEY_CLASS] === self::CLASS_SUPERVISOR;
    }

    public static function isTrainingManager(): bool
    {
        if (!isset($_SESSION[self::KEY_CLASS])) {
            return false;
        }

        return $_SESSION[self::KEY_CLASS] === self::CLASS_TRNG_MGR;
    }
}