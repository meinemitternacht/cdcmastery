<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/5/2017
 * Time: 8:46 PM
 */

namespace CDCMastery\Models\Users;


use Monolog\Logger;

class UserHelpers
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

    /**
     * @param string $username
     * @return null|string
     */
    public function findByUsername(string $username): ?string
    {
        $qry = <<<SQL
SELECT
  uuid
FROM userData
WHERE userHandle = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $username);

        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $stmt->bind_result($uuid);
        $stmt->fetch();
        $stmt->close();

        return $uuid;
    }

    /**
     * @param string $email
     * @return null|string
     */
    public function findByEmail(string $email): ?string
    {
        $qry = <<<SQL
SELECT
  uuid
FROM userData
WHERE userEmail = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $email);

        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $stmt->bind_result($uuid);
        $stmt->fetch();
        $stmt->close();

        return $uuid;
    }
}