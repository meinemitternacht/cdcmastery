<?php
declare(strict_types=1);

namespace CDCMastery\Models\Users\Associations\Afsc;


use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Users\User;
use Monolog\Logger;
use mysqli;

class UserAfscAssociations
{
    public const GROUP_BY_AFSC = 0;
    public const GROUP_BY_USER = 1;

    protected mysqli $db;
    protected Logger $log;

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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ssi',
                               $userUuid,
                               $afscUuid,
                               $authorized) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ss',
                               $userUuid,
                               $afscUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('s', $afscUuid) ||
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return false;
        }

        if (!$stmt->bind_param('ss',
                               $userUuid,
                               $afscUuid) ||
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return false;
        }

        if (!$stmt->bind_param('ss',
                               $user_uuid,
                               $afsc_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }
        if (!$stmt->bind_param('ssi',
                               $userUuid,
                               $afscUuid,
                               $authorized)) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            return;
        }

        foreach ($afscs as $afsc) {
            $afscUuid = $afsc->getUuid();

            if (!$stmt->execute()) {
                DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            }
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ssi',
                               $user_uuid,
                               $afsc_uuid,
                               $authorized)) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            return;
        }

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            if (empty($user->getUuid())) {
                continue;
            }

            $user_uuid = $user->getUuid();

            if (!$stmt->execute()) {
                DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            }
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
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ss',
                               $userUuid,
                               $afscUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('s', $afscUuid) ||
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

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $data = [];
        $out = [];
        switch ($groupBy) {
            case self::GROUP_BY_AFSC:
                while ($row = $res->fetch_assoc()) {
                    $data[ $row[ 'afscUUID' ] ][] = $row[ 'userUUID' ];
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
                    $data[ $row[ 'userUUID' ] ][] = $row[ 'afscUUID' ];
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

    public function countAllByAfsc(Afsc $afsc): int
    {
        $n_users = 0;

        if (empty($afsc->getUuid())) {
            goto out_return;
        }

        $afsc_uuid = $afsc->getUuid();

        $qry = <<<SQL
SELECT
  COUNT(userUUID) AS count
FROM userAFSCAssociations
WHERE afscUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            goto out_return;
        }

        if (!$stmt->bind_param('s', $afsc_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            goto out_return;
        }

        $stmt->bind_result($n_users);
        $stmt->fetch();
        $stmt->close();

        out_return:
        return $n_users ?? 0;
    }

    /**
     * @param Afsc $afsc
     * @param int|null $start
     * @param int|null $limit
     * @param array|null $sort_options
     * @return AfscUserCollection
     */
    public function fetchAllByAfsc(
        Afsc $afsc,
        ?int $start = null,
        ?int $limit = null,
        ?array $sort_options = null
    ): AfscUserCollection {
        $afscUserCollection = new AfscUserCollection();

        if (empty($afsc->getUuid())) {
            goto out_return;
        }

        $afsc_uuid = $afsc->getUuid();
        $afscUserCollection->setAfsc($afsc_uuid);

        if (!$sort_options) {
            $sort_options = [new UserSortOption(UserSortOption::COL_UUID)];
        }

        $join_strs = [];
        $sort_strs = [];
        foreach ($sort_options as $sort_option) {
            if (!$sort_option) {
                continue;
            }

            $join_str_tmp = $sort_option->getJoinClause();

            if ($join_str_tmp) {
                $join_strs[] = $join_str_tmp;
                $sort_strs[] = "{$sort_option->getJoinTgtSortColumn()} {$sort_option->getDirection()}";
                continue;
            }

            $sort_strs[] = "`userData`.`{$sort_option->getColumn()}` {$sort_option->getDirection()}";
        }

        $join_str = $join_strs
            ? implode("\n", $join_strs)
            : null;
        $sort_str = ' ORDER BY ' . implode(', ', $sort_strs);

        $limit_str = null;
        if ($start !== null && $limit !== null) {
            $limit_str = "LIMIT {$start}, {$limit}";
        }

        $qry = <<<SQL
SELECT
  userUUID
FROM userAFSCAssociations
LEFT JOIN userData ON userAFSCAssociations.userUUID = userData.uuid
{$join_str}
WHERE afscUUID = ?
{$sort_str}
{$limit_str}
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            goto out_return;
        }

        if (!$stmt->bind_param('s', $afsc_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            goto out_return;
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

        out_return:
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
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
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
        $userAfscCollection = new UserAfscCollection();

        if (empty($user->getUuid())) {
            goto out_return;
        }

        $userUuid = $user->getUuid();

        $userAfscCollection->setUser($userUuid);

        $qry = <<<SQL
SELECT
    afscUUID,
    userAuthorized
FROM userAFSCAssociations
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            goto out_return;
        }

        if (!$stmt->bind_param('s', $userUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            goto out_return;
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

        out_return:
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ss',
                               $userUuid,
                               $afscUuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('s', $afscUuid) ||
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
DELETE FROM userAFSCAssociations
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
