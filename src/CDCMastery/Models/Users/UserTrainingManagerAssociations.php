<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 9:49 PM
 */

namespace CDCMastery\Models\Users;


use Monolog\Logger;
use mysqli;

class UserTrainingManagerAssociations
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
        $stmt->bind_param(
            'ss',
            $trainingManagerUuid,
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
        $stmt->bind_param(
            'ss',
            $userUuid,
            $trainingManagerUuid
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
        $stmt->bind_param(
            'ss',
            $trainingManagerUuid,
            $userUuid
        );

        $c = count($trainingManagers);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($trainingManagers[$i])) {
                continue;
            }

            if (!$trainingManagers[$i] instanceof User) {
                continue;
            }

            if (empty($trainingManagers[$i]->getUuid())) {
                continue;
            }

            /** @noinspection PhpUnusedLocalVariableInspection */
            $trainingManagerUuid = $trainingManagers[$i]->getUuid();

            if (!$stmt->execute()) {
                /** @todo log */
                continue;
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
        $stmt->bind_param(
            'ss',
            $trainingManagerUuid,
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
        $stmt->bind_param(
            'ss',
            $trainingManagerUuid,
            $userUuid
        );

        if (!$stmt->execute()) {
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
        $stmt->bind_param(
            's',
            $trainingManagerUuid
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
DELETE FROM userTrainingManagerAssociations
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