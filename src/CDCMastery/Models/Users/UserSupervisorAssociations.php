<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 9:36 PM
 */

namespace CDCMastery\Models\Users;


use Monolog\Logger;
use mysqli;

class UserSupervisorAssociations
{
    /**
     * @var mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * UserSupervisorAssociations constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param User $user
     * @param User $supervisor
     */
    public function add(User $user, User $supervisor): void
    {
        if (empty($user->getUuid()) || empty($supervisor->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();
        $supervisorUuid = $supervisor->getUuid();

        $qry = <<<SQL
INSERT INTO userSupervisorAssociations
  (
    supervisorUUID, 
    userUUID
  )
VALUES (?, ?)
ON DUPLICATE KEY UPDATE 
  supervisorUUID=VALUES(supervisorUUID),
  userUUID=VALUES(userUUID)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $supervisorUuid,
            $userUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param User $user
     * @param User $supervisor
     * @return bool
     */
    public function assertAssociated(User $user, User $supervisor): bool
    {
        if (empty($user->getUuid()) || empty($supervisor->getUuid())) {
            return false;
        }

        $userUuid = $user->getUuid();
        $supervisorUuid = $supervisor->getUuid();

        $qry = <<<SQL
SELECT 
  COUNT(*) as count
FROM userSupervisorAssociations
WHERE userUUID = ?
  AND supervisorUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $userUuid,
            $supervisorUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $stmt->bind_result(
            $associated
        );

        $stmt->fetch();
        $stmt->close();

        return (bool)($associated ?? false);
    }

    /**
     * @param User[] $supervisors
     * @param User $user
     */
    public function batchAddSupervisorsForUser(array $supervisors, User $user): void
    {
        if (empty($supervisors) || empty($user->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();

        $qry = <<<SQL
INSERT INTO userSupervisorAssociations
  (
    supervisorUUID, 
    userUUID
  )
VALUES (?, ?)
ON DUPLICATE KEY UPDATE 
  supervisorUUID=VALUES(supervisorUUID),
  userUUID=VALUES(userUUID)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $supervisorUuid,
            $userUuid
        );

        $c = count($supervisors);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($supervisors[$i])) {
                continue;
            }

            if (!$supervisors[$i] instanceof User) {
                continue;
            }

            if (empty($supervisors[$i]->getUuid())) {
                continue;
            }

            /** @noinspection PhpUnusedLocalVariableInspection */
            $supervisorUuid = $supervisors[$i]->getUuid();

            if (!$stmt->execute()) {
                /** @todo log */
                continue;
            }
        }

        $stmt->close();
    }

    /**
     * @param User[] $users
     * @param User $supervisor
     */
    public function batchAddUsersForSupervisor(array $users, User $supervisor): void
    {
        if (empty($users) || empty($supervisor->getUuid())) {
            return;
        }

        $supervisorUuid = $supervisor->getUuid();

        $qry = <<<SQL
INSERT INTO userSupervisorAssociations
  (
    supervisorUUID, 
    userUUID
  )
VALUES (?, ?)
ON DUPLICATE KEY UPDATE 
  supervisorUUID=VALUES(supervisorUUID),
  userUUID=VALUES(userUUID)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $supervisorUuid,
            $userUuid
        );

        $c = count($users);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($users[$i])) {
                continue;
            }

            if (!$users[$i] instanceof User) {
                continue;
            }

            if (empty($users[$i]->getUuid())) {
                continue;
            }

            /** @noinspection PhpUnusedLocalVariableInspection */
            $userUuid = $users[$i]->getUuid();

            if (!$stmt->execute()) {
                /** @todo log */
                continue;
            }
        }

        $stmt->close();
    }

    /**
     * @param User $user
     * @param User $supervisor
     */
    public function remove(User $user, User $supervisor): void
    {
        if (empty($user->getUuid()) || empty($supervisor->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();
        $supervisorUuid = $supervisor->getUuid();

        $qry = <<<SQL
DELETE FROM userSupervisorAssociations
WHERE supervisorUUID = ?
  AND userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $supervisorUuid,
            $userUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param User $supervisor
     */
    public function removeAllBySupervisor(User $supervisor): void
    {
        if (empty($supervisor->getUuid())) {
            return;
        }

        $supervisorUuid = $supervisor->getUuid();

        $qry = <<<SQL
DELETE FROM userSupervisorAssociations
WHERE supervisorUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            's',
            $supervisorUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param User $user
     */
    public function removeAllByUser(User $user): void
    {
        if (empty($user->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();

        $qry = <<<SQL
DELETE FROM userSupervisorAssociations
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            's',
            $userUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }
}