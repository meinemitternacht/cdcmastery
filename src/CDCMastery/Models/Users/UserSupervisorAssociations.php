<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 9:36 PM
 */

namespace CDCMastery\Models\Users;


use CDCMastery\Models\Users\Roles\Role;
use Monolog\Logger;
use mysqli;

class UserSupervisorAssociations
{
    protected mysqli $db;
    protected Logger $log;

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

        foreach ($supervisors as $supervisor) {
            $supervisorUuid = $supervisor->getUuid();

            $stmt->bind_param(
                'ss',
                $supervisorUuid,
                $userUuid
            );

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

        foreach ($users as $user) {
            $userUuid = $user->getUuid();

            $stmt->bind_param(
                'ss',
                $supervisorUuid,
                $userUuid
            );

            if (!$stmt->execute()) {
                /** @todo log */
                continue;
            }
        }

        $stmt->close();
    }

    /**
     * @param User $user
     * @return string[]
     *  A list of supervisor user UUIDs
     */
    public function fetchAllByUser(User $user): array
    {
        if (empty($user->getUuid())) {
            return [];
        }

        $user_uuid = $user->getUuid();

        $qry = <<<SQL
SELECT supervisorUUID
FROM userSupervisorAssociations
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return [];
        }

        if (!$stmt->bind_param('s', $user_uuid) ||
            !$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result($su_uuid);

        $data = [];
        while ($stmt->fetch()) {
            $data[] = $su_uuid;
        }

        $stmt->close();
        return $data;
    }

    /**
     * @param User $user
     * @return string[]
     *  A list of subordinate user UUIDs
     */
    public function fetchAllBySupervisor(User $user): array
    {
        if (empty($user->getUuid())) {
            return [];
        }

        $su_uuid = $user->getUuid();

        $qry = <<<SQL
SELECT userUUID
FROM userSupervisorAssociations
WHERE supervisorUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return [];
        }

        if (!$stmt->bind_param('s', $su_uuid) ||
            !$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result($user_uuid);

        $data = [];
        while ($stmt->fetch()) {
            $data[] = $user_uuid;
        }

        $stmt->close();
        return $data;
    }

    /**
     * @param User $user
     * @return string[]
     *  A list of available user and supervisor UUIDs
     */
    public function fetchUnassociatedBySupervisor(User $user): array
    {
        if (empty($user->getUuid())) {
            return [];
        }

        $su_uuid = $user->getUuid();
        $base_uuid = $user->getBase();
        $role_1 = Role::TYPE_USER;
        $role_2 = Role::TYPE_SUPERVISOR;

        $qry = <<<SQL
SELECT t1.uuid
FROM userData t1
LEFT JOIN roleList t2 ON t1.userRole = t2.uuid
WHERE t1.uuid NOT IN 
      (
          SELECT userUUID FROM userSupervisorAssociations
          WHERE supervisorUUID = ?
      )
  AND t1.userBase = ?
  AND t2.roleType IN (?, ?)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return [];
        }

        if (!$stmt->bind_param('ssss', $su_uuid, $base_uuid, $role_1, $role_2) ||
            !$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result($user_uuid);

        $data = [];
        while ($stmt->fetch()) {
            $data[] = $user_uuid;
        }

        $stmt->close();
        return $data;
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