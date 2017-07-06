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
    const KEY_USER_NAME = 'name';
    const KEY_USER_UUID = 'uuid';

    /**
     * @return null|string
     */
    public static function getUserName(): ?string
    {
        return $_SESSION[self::KEY_USER_NAME] ?? null;
    }

    /**
     * @return null|string
     */
    public static function getUserUuid(): ?string
    {
        return $_SESSION[self::KEY_USER_UUID] ?? null;
    }
}