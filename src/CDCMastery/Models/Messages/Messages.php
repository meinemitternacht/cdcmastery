<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 1/22/2017
 * Time: 12:02 AM
 */

namespace CDCMastery\Models;


class Messages
{
    const SESS_KEY = 'messageStore';

    const SUCCESS = 'success';
    const INFO = 'info';
    const WARNING = 'warning';
    const DANGER = 'danger';

    const VALID_TYPES = [
        self::SUCCESS,
        self::INFO,
        self::WARNING,
        self::DANGER
    ];

    /**
     * @param string $messageType
     * @param string $message
     */
    public static function add(string $messageType, string $message): void
    {
        if (!in_array($messageType, self::VALID_TYPES)) {
            return;
        }

        if (!isset($_SESSION[self::SESS_KEY])) {
            $_SESSION[self::SESS_KEY] = [];
        }

        if (!isset($_SESSION[self::SESS_KEY][$messageType])) {
            $_SESSION[self::SESS_KEY][$messageType] = [];
        }

        array_push($_SESSION[self::SESS_KEY][$messageType], $message);
    }

    /**
     * @return array
     */
    public static function get(): array
    {
        if (!isset($_SESSION[self::SESS_KEY])) {
            return [];
        }

        $messages = $_SESSION[self::SESS_KEY];
        $_SESSION[self::SESS_KEY] = [];

        return $messages;
    }
}