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
    const GROUP_BY_AFSC = 0;
    const GROUP_BY_USER = 1;

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
     * @param Afsc $afsc
     * @return bool
     */
    public function assertAuthorized(User $user, Afsc $afsc): bool
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
            $authorized
        );

        $stmt->fetch();
        $stmt->close();

        return (bool)($authorized ?? false);
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
     * @param int $groupBy
     * @return UserAfscCollection[]|AfscUserCollection[]
     */
    public function fetchAll(int $groupBy): array
    {
        $qry = <<<SQL
SELECT
  afscUUID,
  userUUID
FROM userAFSCAssociations
SQL;

        switch ($groupBy) {
            case self::GROUP_BY_AFSC:
                $qry .= ' ORDER BY afscUUID ASC';
                break;
            case self::GROUP_BY_USER:
                $qry .= ' ORDER BY userUUID ASC';
                break;
            default:
                return [];
                break;
        }

        $res = $this->db->query($qry);

        $data = [];
        $out = [];
        switch ($groupBy) {
            case self::GROUP_BY_AFSC:
                while ($row = $res->fetch_assoc()) {
                    $data[$row['afscUUID']][] = $row['userUUID'];
                }

                $res->free();

                foreach ($data as $afscUuid => $userList) {
                    $afscUserCollection = new AfscUserCollection();
                    $afscUserCollection->setAfsc($afscUuid);
                    $afscUserCollection->setUsers($userList);

                    $out[] = $afscUserCollection;
                }
                break;
            case self::GROUP_BY_USER:
                while ($row = $res->fetch_assoc()) {
                    $data[$row['userUUID']][] = $row['afscUUID'];
                }

                $res->free();

                foreach ($data as $userUuid => $afscList) {
                    $userAfscCollection = new UserAfscCollection();
                    $userAfscCollection->setUser($userUuid);
                    $userAfscCollection->setAfscs($afscList);

                    $out[] = $userAfscCollection;
                }
                break;
            default:
                return [];
                break;
        }

        return $out;
    }

    /**
     * @param Afsc $afsc
     * @return AfscUserCollection
     */
    public function fetchAllByAfsc(Afsc $afsc): AfscUserCollection
    {
        if (empty($afsc->getUuid())) {
            return new AfscUserCollection();
        }

        $afscUserCollection = new AfscUserCollection();
        $afscUserCollection->setAfsc($afsc->getUuid());

        $afscUuid = $afsc->getUuid();

        $qry = <<<SQL
SELECT
  userUUID
FROM userAFSCAssociations
WHERE afscUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $afscUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return $afscUserCollection;
        }

        $stmt->bind_result($userUuid);

        $userList = [];
        while ($stmt->fetch()) {
            if (!isset($userUuid) || $userUuid === null) {
                continue;
            }

            $userList[] = $userUuid;
        }

        $stmt->close();

        $afscUserCollection->setUsers($userList);

        return $afscUserCollection;
    }

    /**
     * @param User $user
     * @return UserAfscCollection
     */
    public function fetchAllByUser(User $user): UserAfscCollection
    {
        if (empty($user->getUuid())) {
            return new UserAfscCollection();
        }

        $userUuid = $user->getUuid();

        $userAfscCollection = new UserAfscCollection();
        $userAfscCollection->setUser($userUuid);

        $qry = <<<SQL
SELECT
  afscUUID
FROM userAFSCAssociations
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $userUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return $userAfscCollection;
        }

        $stmt->bind_result($afscUuid);

        $afscList = [];
        while ($stmt->fetch()) {
            if (!isset($afscUuid) || $afscUuid === null) {
                continue;
            }

            $afscList[] = $afscUuid;
        }

        $stmt->close();

        $userAfscCollection->setAfscs($afscList);

        return $userAfscCollection;
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