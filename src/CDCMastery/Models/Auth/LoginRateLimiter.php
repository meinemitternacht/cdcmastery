<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 1/6/2017
 * Time: 10:27 PM
 */

namespace CDCMastery\Models\Auth;

/**
 * Class LoginRateLimiter
 * @package CDCMastery\Models\Auth
 */
class LoginRateLimiter
{
    const RATE_LIMIT_DURATION = 300;
    const RATE_LIMIT_THRESHOLD = 10;

    const SESS_KEY_COUNT = 'rate-limit-attempts';
    const SESS_KEY_TIME = 'rate-limit-time';

    /**
     * @return bool
     */
    public static function assertLimited(): bool
    {
        if (!isset($_SESSION[self::SESS_KEY_COUNT]) || !isset($_SESSION[self::SESS_KEY_TIME])) {
            return false;
        }

        $limitStartTime = filter_var(
            $_SESSION[self::SESS_KEY_TIME],
            FILTER_VALIDATE_INT
        );
        $limitCount = filter_var(
            $_SESSION[self::SESS_KEY_COUNT],
            FILTER_VALIDATE_INT
        );

        if (!is_int($limitStartTime) || !is_int($limitCount)) {
            return false;
        }

        if (($limitStartTime + self::RATE_LIMIT_DURATION) < time()) {
            return false;
        }

        if ($limitCount >= self::RATE_LIMIT_THRESHOLD) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function increment(): bool
    {
        self::initialize();

        $_SESSION[self::SESS_KEY_COUNT]++;

        return true;
    }

    /**
     * @return bool
     */
    private static function initialize(): bool
    {
        if (!isset($_SESSION[self::SESS_KEY_COUNT])) {
            $_SESSION[self::SESS_KEY_COUNT] = 0;
        }

        if (!isset($_SESSION[self::SESS_KEY_TIME])) {
            $_SESSION[self::SESS_KEY_TIME] = time();
        }

        return true;
    }

    /**
     * @return bool
     */
    public static function reset(): bool
    {
        if (isset($_SESSION[self::SESS_KEY_COUNT])) {
            unset($_SESSION[self::SESS_KEY_COUNT]);
        }

        if (isset($_SESSION[self::SESS_KEY_TIME])) {
            unset($_SESSION[self::SESS_KEY_TIME]);
        }

        return true;
    }
}