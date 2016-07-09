<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 7/8/16
 * Time: 3:06 AM
 */

namespace CDCMastery;

class Application
{
    public $timeStart;

    public function __construct()
    {
        ob_start();
        $this->ApplicationStartup();

        /**
         * Maintenance mode short-circuit
         */
        $maintenanceMode = false;
        if ($maintenanceMode == true) {
            include __DIR__ . '/../app/errors/maintenance.php';
            exit();
        }
    }

    public function ApplicationStartup()
    {
        $this->timeStart = microtime(true);
        ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
        date_default_timezone_set("UTC"); /* All times should be UTC unless overridden by a user's time zone */
    }

    public function ApplicationShutdown()
    {
        /**
         * After processing everything, flush the output buffer and destroy the router
         */
        ob_end_flush();
    }

    public function getTimeStart()
    {
        return $this->timeStart;
    }
}