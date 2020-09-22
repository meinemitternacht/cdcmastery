<?php

namespace CDCMastery\Models\Users;


use CDCMastery\Models\CdcData\Afsc;
use Monolog\Logger;
use mysqli;

class UserAfscAssociations
{
    public const GROUP_BY_AFSC = 0;
    public const GROUP_BY_USER = 1;

    /**
     * @var mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * UserAfscAssociations constructor.
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
     * @param Afsc|null $afsc
     * @param string|null $afsc_uuid
     * @return bool
     */
    public function assertAuthorized(User $user, ?Afsc $afsc, ?string $afsc_uuid = null): bool
    {
        if ((!$afsc && !$afsc_uuid) || !$user->getUuid()) {
            return false;
        }

        $user_uuid = $user->getUuid();
        $afsc_uuid = $afsc
            ? $afsc->getUuid()
            : $afsc_uuid;

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
            $user_uuid,
            $afsc_uuid
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
        if (!$afscs || !$user->getUuid()) {
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

        foreach ($afscs as $afsc) {
            $afscUuid = $afsc->getUuid();

            $stmt->bind_param(
                'ssi',
                $userUuid,
                $afscUuid,
                $authorized
            );

            if (!$stmt->execute()) {
                /** @todo log */
                continue;
            }

            $stmt->reset();
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

        $afsc_uuid = $afsc->getUuid();

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

        $user_uuid = null;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ssi',
            $user_uuid,
            $afsc_uuid,
            $authorized
        );

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            if (empty($user->getUuid())) {
                continue;
            }

            $user_uuid = $user->getUuid();

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

    public function countPending(): int
    {
        $qry = <<<SQL
SELECT COUNT(*) AS count FROM userAFSCAssociations WHERE userAuthorized = 0
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return 0;
        }

        $row = $res->fetch_assoc();
        $res->free();

        return (int)($row[ 'count' ] ?? 0);
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
            if (!isset($userUuid)) {
                continue;
            }

            $userList[] = $userUuid;
        }

        $stmt->close();

        $afscUserCollection->setUsers($userList);

        return $afscUserCollection;
    }

    /**
     * @return UserAfscCollection[]
     */
    public function fetchAllPending(): array
    {
        $qry = <<<SQL
SELECT
    afscUUID,
    userUUID,
    userAuthorized
FROM userAFSCAssociations
WHERE userAuthorized = 0
ORDER BY userUUID
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return [];
        }

        $data = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($data[ $row[ 'userUUID' ] ])) {
                $data[ $row[ 'userUUID' ] ] = [
                    'afsc_list' => [],
                    'pending' => [],
                ];
            }

            $data[ $row[ 'userUUID' ] ][ 'afsc_list' ][] = $row[ 'afscUUID' ];
            $data[ $row[ 'userUUID' ] ][ 'pending' ][] = $row[ 'afscUUID' ];
        }

        $res->free();

        if (!$data) {
            return [];
        }

        $assocs = [];
        foreach ($data as $user_uuid => $afsc_assocs) {
            $assoc = new UserAfscCollection();
            $assoc->setUser($user_uuid);
            $assoc->setAfscs($afsc_assocs[ 'afsc_list' ]);
            $assoc->setAuthorized([]);
            $assoc->setPending($afsc_assocs[ 'pending' ]);
            $assocs[ $user_uuid ] = $assoc;
        }

        return $assocs;
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
    afscUUID,
    userAuthorized
FROM userAFSCAssociations
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $userUuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return $userAfscCollection;
        }

        $stmt->bind_result($afscUuid, $authorized);

        $afscList = [];
        $afscsAuthorized = [];
        $afscsPending = [];
        while ($stmt->fetch()) {
            if (!isset($afscUuid)) {
                continue;
            }

            $afscList[] = $afscUuid;

            if ($authorized) {
                $afscsAuthorized[] = $afscUuid;
                continue;
            }

            $afscsPending[] = $afscUuid;
        }

        $stmt->close();

        $userAfscCollection->setAfscs($afscList);
        $userAfscCollection->setAuthorized($afscsAuthorized);
        $userAfscCollection->setPending($afscsPending);

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