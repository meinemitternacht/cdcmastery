<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 2:27 PM
 */

namespace CDCMastery\Helpers;


class AppHelpers
{
    /**
     * @param string $destination
     * @return string
     */
    public static function redirect(string $destination): string
    {
        session_write_close();

        $protocol = isset($_SERVER['HTTPS'])
            ? 'https://'
            : 'http://';

        header('Location: ' . $protocol . $_SERVER['HTTP_HOST'] . $destination);
        ob_end_flush();
        return exit();
    }
}