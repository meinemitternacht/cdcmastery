<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/4/2017
 * Time: 9:06 PM
 */

namespace CDCMastery\Models\Auth;


use CDCMastery\Models\Users\User;
use Monolog\Logger;

class AuthProcessor
{
    /**
     * @var \mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * AuthProcessor constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    public function login(User $user): void
    {

    }

    public function logout(User $user): void
    {

    }
}