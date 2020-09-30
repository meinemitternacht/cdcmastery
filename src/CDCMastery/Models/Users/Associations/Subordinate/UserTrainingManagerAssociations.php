<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 9:49 PM
 */

namespace CDCMastery\Models\Users\Associations\Subordinate;


use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\User;
use Monolog\Logger;
use mysqli;

class UserTrainingManagerAssociations
{
    protected mysqli $db;
    protected Logger $log;

    /**
     * UserTrainingManagerAssociations constructor.
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
     * @param User $trainingManager
     */
    public function add(User $user, User $trainingManager): void
    {
        if (empty($user->getUuid()) || empty($trainingManager->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();
        $trainingManagerUuid = $trainingManager->getUuid();

        $qry = <<<SQL
INSERT INTO userTrainingManagerAssociations
  (
    trainingManagerUUID, 
    userUUID
  )
VALUES (?, ?)
ON DUPLICATE KEY UPDATE 
  trainingManagerUUID=VALUES(trainingManagerUUID),
  userUUID=VALUES(userUUID)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ss',
                               $trainingManagerUuid,
                               $userUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param User $user
     * @param User $trainingManager
     * @return bool
     */
    public function assertAssociated(User $user, User $trainingManager): bool
    {
        if (empty($user->getUuid()) || empty($trainingManager->getUuid())) {
            return false;
        }

        $userUuid = $user->getUuid();
        $trainingManagerUuid = $trainingManager->getUuid();

        $qry = <<<SQL
SELECT 
  COUNT(*) as count
FROM userTrainingManagerAssociations
WHERE userUUID = ?
  AND trainingManagerUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return false;
        }

        if (!$stmt->bind_param('ss',
                               $userUuid,
                               $trainingManagerUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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
     * @param User[] $trainingManagers
     * @param User $user
     */
    public function batchAddTrainingManagersForUser(array $trainingManagers, User $user): void
    {
        if (empty($trainingManagers) || empty($user->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();

        $qry = <<<SQL
INSERT INTO userTrainingManagerAssociations
  (
    trainingManagerUUID, 
    userUUID
  )
VALUES (?, ?)
ON DUPLICATE KEY UPDATE 
  trainingManagerUUID=VALUES(trainingManagerUUID),
  userUUID=VALUES(userUUID)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        foreach ($trainingManagers as $trainingManager) {
            $trainingManagerUuid = $trainingManager->getUuid();

            if (!$stmt->bind_param('ss',
                                   $trainingManagerUuid,
                                   $userUuid) ||
                !$stmt->execute()) {
                DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            }
        }

        $stmt->close();
    }

    /**
     * @param User[] $users
     * @param User $trainingManager
     */
    public function batchAddUsersForTrainingManager(array $users, User $trainingManager): void
    {
        if (empty($users) || empty($trainingManager->getUuid())) {
            return;
        }

        $trainingManagerUuid = $trainingManager->getUuid();

        $qry = <<<SQL
INSERT INTO userTrainingManagerAssociations
  (
    trainingManagerUUID, 
    userUUID
  )
VALUES (?, ?)
ON DUPLICATE KEY UPDATE 
  trainingManagerUUID=VALUES(trainingManagerUUID),
  userUUID=VALUES(userUUID)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        foreach ($users as $user) {
            $userUuid = $user->getUuid();

            if (!$stmt->bind_param('ss',
                                   $trainingManagerUuid,
                                   $userUuid) ||
                !$stmt->execute()) {
                DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            }
        }

        $stmt->close();
    }

    /**
     * @param User $user
     * @return string[]
     *  A list of training manager user UUIDs
     */
    public function fetchAllByUser(User $user): array
    {
        if (empty($user->getUuid())) {
            return [];
        }

        $user_uuid = $user->getUuid();

        $qry = <<<SQL
SELECT trainingManagerUUID
FROM userTrainingManagerAssociations
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$stmt->bind_param('s', $user_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return [];
        }

        $stmt->bind_result($tm_uuid);

        $data = [];
        while ($stmt->fetch()) {
            $data[] = $tm_uuid;
        }

        $stmt->close();
        return $data;
    }

    /**
     * @param User $user
     * @return string[]
     *  A list of subordinate user UUIDs
     */
    public function fetchAllByTrainingManager(User $user): array
    {
        if (empty($user->getUuid())) {
            return [];
        }

        $tm_uuid = $user->getUuid();

        $qry = <<<SQL
SELECT userUUID
FROM userTrainingManagerAssociations
WHERE trainingManagerUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$stmt->bind_param('s', $tm_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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
     *  A list of available user, supervisor, and training manager UUIDs
     */
    public function fetchUnassociatedByTrainingManager(User $user): array
    {
        if (empty($user->getUuid())) {
            return [];
        }

        $tm_uuid = $user->getUuid();
        $base_uuid = $user->getBase();
        $role_1 = Role::TYPE_USER;
        $role_2 = Role::TYPE_SUPERVISOR;
        $role_3 = Role::TYPE_TRAINING_MANAGER;

        $qry = <<<SQL
SELECT t1.uuid
FROM userData t1
LEFT JOIN roleList t2 ON t1.userRole = t2.uuid
WHERE t1.uuid NOT IN 
      (
          SELECT userUUID FROM userTrainingManagerAssociations
          WHERE trainingManagerUUID = ?
      )
  AND t1.userBase = ?
  AND t2.roleType IN (?, ?, ?)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        if (!$stmt->bind_param('sssss', $tm_uuid, $base_uuid, $role_1, $role_2, $role_3) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return [];
        }

        $stmt->bind_result($user_uuid);

        $data = [];
        while ($stmt->fetch()) {
            if ($user_uuid === $user->getUuid()) {
                continue;
            }

            $data[] = $user_uuid;
        }

        $stmt->close();
        return $data;
    }

    /**
     * @param User $user
     * @param User $trainingManager
     */
    public function remove(User $user, User $trainingManager): void
    {
        if (empty($user->getUuid()) || empty($trainingManager->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();
        $trainingManagerUuid = $trainingManager->getUuid();

        $qry = <<<SQL
DELETE FROM userTrainingManagerAssociations
WHERE trainingManagerUUID = ?
  AND userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ss',
                               $trainingManagerUuid,
                               $userUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param User $trainingManager
     */
    public function removeAllByTrainingManager(User $trainingManager): void
    {
        if (empty($trainingManager->getUuid())) {
            return;
        }

        $trainingManagerUuid = $trainingManager->getUuid();

        $qry = <<<SQL
DELETE FROM userTrainingManagerAssociations
WHERE trainingManagerUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('s', $trainingManagerUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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
DELETE FROM userTrainingManagerAssociations
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('s', $userUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }
}