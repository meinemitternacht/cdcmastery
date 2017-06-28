<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 8:54 PM
 */

namespace CDCMastery\Models\Users;


use CDCMastery\Models\CdcData\Afsc;
use Monolog\Logger;

class UserAfscAssociations
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
     * UserAfscAssociations constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param User $user
     * @param Afsc $afsc
     * @param bool $authorized
     */
    public function add(User $user, Afsc $afsc, bool $authorized): void
    {
        if (empty($user->getUuid()) || empty($afsc->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();
        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
INSERT INTO userAFSCAssociations
  ( 
    userUUID, 
    afscUUID,
    userAuthorized
  )
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE 
  userUUID=VALUES(userUUID),
  afscUUID=VALUES(afscUUID),
  userAuthorized=VALUES(userAuthorized)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ssi',
            $userUuid,
            $afscUuid,
            $authorized
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param User $user
     * @param Afsc $afsc
     */
    public function authorize(User $user, Afsc $afsc): void
    {
        if (empty($user->getUuid()) || empty($afsc->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();
        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
UPDATE userAFSCAssociations
SET userAuthorized = 1
WHERE userUUID = ?
  AND afscUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $userUuid,
            $afscUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param Afsc $afsc
     */
    public function authorizeAllByAfsc(Afsc $afsc): void
    {
        if (empty($afsc->getUuid())) {
            return;
        }

        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
UPDATE userAFSCAssociations
SET userAuthorized = 1
WHERE afscUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            's',
            $afscUuid
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
    public function authorizeAllByUser(User $user): void
    {
        if (empty($user->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();

        $qry = <<<SQL
UPDATE userAFSCAssociations
SET userAuthorized = 1
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

    /**
     * @param User $user
     * @param Afsc $afsc
     * @return bool
     */
    public function assertAssociated(User $user, Afsc $afsc): bool
    {
        if (empty($user->getUuid()) || empty($afsc->getUuid())) {
            return false;
        }

        $userUuid = $user->getUuid();
        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
SELECT 
  COUNT(*) as count
FROM userAFSCAssociations
WHERE userUUID = ?
  AND afscUUID = ?
  AND userAuthorized = 1
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $userUuid,
            $afscUuid
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
     * @param User $user
     * @param Afsc[] $afscs
     * @param bool $authorized
     */
    public function batchAddAfscsForUser(User $user, array $afscs, bool $authorized): void
    {
        if (empty($user->getUuid()) || empty($afscs)) {
            return;
        }

        $userUuid = $user->getUuid();

        $qry = <<<SQL
INSERT INTO userAFSCAssociations
  ( 
    userUUID, 
    afscUUID,
    userAuthorized
  )
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE 
  userUUID=VALUES(userUUID),
  afscUUID=VALUES(afscUUID),
  userAuthorized=VALUES(userAuthorized)
SQL;

        $afscUuid = null;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ssi',
            $userUuid,
            $afscUuid,
            $authorized
        );

        $c = count($afscs);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($afscs[$i])) {
                continue;
            }

            if (!$afscs[$i] instanceof Afsc) {
                continue;
            }

            if (empty($afscs[$i]->getUuid())) {
                continue;
            }

            /** @noinspection PhpUnusedLocalVariableInspection */
            $afscUuid = $afscs[$i]->getUuid();

            if (!$stmt->execute()) {
                /** @todo log */
                continue;
            }
        }

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param User[] $users
     * @param Afsc $afsc
     * @param bool $authorized
     */
    public function batchAddUsersForAfsc(array $users, Afsc $afsc, bool $authorized): void
    {
        if (empty($users) || empty($afsc->getUuid())) {
            return;
        }

        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
INSERT INTO userAFSCAssociations
  ( 
    userUUID, 
    afscUUID,
    userAuthorized
  )
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE 
  userUUID=VALUES(userUUID),
  afscUUID=VALUES(afscUUID),
  userAuthorized=VALUES(userAuthorized)
SQL;

        $userUuid = null;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ssi',
            $userUuid,
            $afscUuid,
            $authorized
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

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param User $user
     * @param Afsc $afsc
     */
    public function deauthorize(User $user, Afsc $afsc): void
    {
        if (empty($user->getUuid()) || empty($afsc->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();
        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
UPDATE userAFSCAssociations
SET userAuthorized = 0
WHERE userUUID = ?
  AND afscUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $userUuid,
            $afscUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param Afsc $afsc
     */
    public function deauthorizeAllByAfsc(Afsc $afsc): void
    {
        if (empty($afsc->getUuid())) {
            return;
        }

        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
UPDATE userAFSCAssociations
SET userAuthorized = 0
WHERE afscUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            's',
            $afscUuid
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
    public function deauthorizeAllByUser(User $user): void
    {
        if (empty($user->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();

        $qry = <<<SQL
UPDATE userAFSCAssociations
SET userAuthorized = 0
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

    /**
     * @param User $user
     * @param Afsc $afsc
     */
    public function remove(User $user, Afsc $afsc): void
    {
        if (empty($user->getUuid()) || empty($afsc->getUuid())) {
            return;
        }

        $userUuid = $user->getUuid();
        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
DELETE FROM userAFSCAssociations
WHERE userUUID = ?
  AND afscUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $userUuid,
            $afscUuid
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param Afsc $afsc
     */
    public function removeAllByAfsc(Afsc $afsc): void
    {
        if (empty($afsc->getUuid())) {
            return;
        }

        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
DELETE FROM userAFSCAssociations
WHERE afscUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            's',
            $afscUuid
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
DELETE FROM userAFSCAssociations
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