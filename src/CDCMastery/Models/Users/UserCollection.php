<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 8:07 PM
 */

namespace CDCMastery\Models\Users;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use DateTime;
use Monolog\Logger;
use mysqli;
use RuntimeException;

class UserCollection
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
     * UserCollection constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    private function create_objects(array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $user = new User();
            $user->setUuid($row[ 'uuid' ] ?? '');
            $user->setFirstName($row[ 'userFirstName' ] ?? '');
            $user->setLastName($row[ 'userLastName' ] ?? '');
            $user->setHandle($row[ 'userHandle' ] ?? '');
            $user->setPassword($row[ 'userPassword' ] ?? '');
            $user->setLegacyPassword($row[ 'userLegacyPassword' ] ?? '');
            $user->setEmail($row[ 'userEmail' ] ?? '');
            $user->setRank($row[ 'userRank' ] ?? '');

            if (($row[ 'userDateRegistered' ] ?? null) !== null) {
                $user->setDateRegistered(
                    DateTime::createFromFormat(
                        DateTimeHelpers::DT_FMT_DB,
                        $row[ 'userDateRegistered' ] ?? ''
                    )
                );
            }

            if (($row[ 'userLastLogin' ] ?? null) !== null) {
                $user->setLastLogin(
                    DateTime::createFromFormat(
                        DateTimeHelpers::DT_FMT_DB,
                        $row[ 'userLastLogin' ] ?? ''
                    )
                );
            }

            if (($row[ 'userLastActive' ] ?? null) !== null) {
                $user->setLastActive(
                    DateTime::createFromFormat(
                        DateTimeHelpers::DT_FMT_DB,
                        $row[ 'userLastActive' ] ?? ''
                    )
                );
            }

            $user->setTimeZone($row[ 'userTimeZone' ] ?? '');
            $user->setRole($row[ 'userRole' ]);
            $user->setOfficeSymbol($row[ 'userOfficeSymbol' ]);
            $user->setBase($row[ 'userBase' ]);
            $user->setDisabled((bool)($row[ 'userDisabled' ] ?? false));
            $user->setReminderSent((bool)($row[ 'reminderSent' ] ?? false));

            $out[ $row[ 'uuid' ] ] = $user;
        }

        return $out;
    }

    public function count(): int
    {
        $qry = <<<SQL
SELECT COUNT(*) AS count FROM userData
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return 0;
        }

        $row = $res->fetch_assoc();
        $res->free();

        return (int)$row[ 'count' ];
    }

    /**
     * @param string $uuid
     */
    public function delete(string $uuid): void
    {
        if (empty($uuid)) {
            return;
        }

        $uuid = $this->db->real_escape_string($uuid);

        $qry = <<<SQL
DELETE FROM userData
WHERE uuid = '{$uuid}'
SQL;

        $this->db->query($qry);
    }

    /**
     * @param string $uuid
     * @return User
     */
    public function fetch(string $uuid): User
    {
        if (empty($uuid)) {
            return new User();
        }

        $qry = <<<SQL
SELECT
  uuid,
  userFirstName,
  userLastName,
  userHandle,
  userPassword,
  userLegacyPassword,
  userEmail,
  userRank,
  userDateRegistered,
  userLastLogin,
  userLastActive,
  userTimeZone,
  userRole,
  userOfficeSymbol,
  userBase,
  userDisabled,
  reminderSent
FROM userData
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new User();
        }

        $stmt->bind_result(
            $_uuid,
            $firstName,
            $lastName,
            $handle,
            $password,
            $legacyPassword,
            $email,
            $rank,
            $dateRegistered,
            $lastLogin,
            $lastActive,
            $timeZone,
            $role,
            $officeSymbol,
            $base,
            $disabled,
            $reminderSent
        );

        $stmt->fetch();
        $stmt->close();

        $row = [
            'uuid' => $_uuid,
            'userFirstName' => $firstName,
            'userLastName' => $lastName,
            'userHandle' => $handle,
            'userPassword' => $password,
            'userLegacyPassword' => $legacyPassword,
            'userEmail' => $email,
            'userRank' => $rank,
            'userDateRegistered' => $dateRegistered,
            'userLastLogin' => $lastLogin,
            'userLastActive' => $lastActive,
            'userTimeZone' => $timeZone,
            'userRole' => $role,
            'userOfficeSymbol' => $officeSymbol,
            'userBase' => $base,
            'userDisabled' => $disabled,
            'reminderSent' => $reminderSent,
        ];

        $user = $this->create_objects([$row])[ $_uuid ] ?? null;

        if ($user === null) {
            throw new RuntimeException('unable to create user object');
        }

        return $user;
    }

    /**
     * @param UserSortOption[]|null $sort_options
     * @param int|null $start
     * @param int|null $limit
     * @return User[]
     */
    public function fetchAll(?array $sort_options = null, ?int $start = null, ?int $limit = null): array
    {
        if (!$sort_options) {
            $sort_options = [new UserSortOption(UserSortOption::COL_UUID)];
        }

        $join_strs = [];
        $sort_strs = [];
        foreach ($sort_options as $sort_option) {
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
# noinspection SqlResolve

SELECT
  userData.uuid,
  userFirstName,
  userLastName,
  userHandle,
  userPassword,
  userLegacyPassword,
  userEmail,
  userRank,
  userDateRegistered,
  userLastLogin,
  userLastActive,
  userTimeZone,
  userRole,
  userOfficeSymbol,
  userBase,
  userDisabled,
  reminderSent
FROM userData
{$join_str}
{$sort_str}
{$limit_str}
SQL;

        $res = $this->db->query($qry);

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ]) || $row[ 'uuid' ] === null) {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();

        return $this->create_objects($rows);
    }

    /**
     * @param string[] $uuidList
     * @param array|null $sort_options
     * @return User[]
     */
    public function fetchArray(array $uuidList, ?array $sort_options = null): array
    {
        if (empty($uuidList)) {
            return [];
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);

        if (!$sort_options) {
            $sort_options = [new UserSortOption(UserSortOption::COL_UUID)];
        }

        $join_strs = [];
        $sort_strs = [];
        foreach ($sort_options as $sort_option) {
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

        $qry = <<<SQL
# noinspection SqlResolve

SELECT
  userData.uuid,
  userFirstName,
  userLastName,
  userHandle,
  userPassword,
  userLegacyPassword,
  userEmail,
  userRank,
  userDateRegistered,
  userLastLogin,
  userLastActive,
  userTimeZone,
  userRole,
  userOfficeSymbol,
  userBase,
  userDisabled,
  reminderSent
FROM userData
{$join_str}
WHERE userData.uuid IN ('{$uuidListString}')
{$sort_str}
SQL;

        $res = $this->db->query($qry);

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ]) || $row[ 'uuid' ] === null) {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();

        return array_intersect_key(
            $this->create_objects($rows),
            array_flip($uuidList)
        );
    }

    /* @todo proper filter function for each column */

    public function filterByBase(Base $base, ?array $sort_options = null): array
    {
        if (!$base->getUuid()) {
            return [];
        }

        if (!$sort_options) {
            $sort_options = [new UserSortOption(UserSortOption::COL_UUID)];
        }

        $join_strs = [];
        $sort_strs = [];
        foreach ($sort_options as $sort_option) {
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

        $qry = <<<SQL
# noinspection SqlResolve

SELECT
  userData.uuid,
  userFirstName,
  userLastName,
  userHandle,
  userPassword,
  userLegacyPassword,
  userEmail,
  userRank,
  userDateRegistered,
  userLastLogin,
  userLastActive,
  userTimeZone,
  userRole,
  userOfficeSymbol,
  userBase,
  userDisabled,
  reminderSent
FROM userData
{$join_str}
WHERE userBase = ?
{$sort_str}
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return [];
        }

        $base_uuid = $base->getUuid();
        if (!$stmt->bind_param('s', $base_uuid) ||
            !$stmt->execute()) {
            $stmt->close();
            return [];
        }

        $stmt->bind_result(
            $uuid,
            $userFirstName,
            $userLastName,
            $userHandle,
            $userPassword,
            $userLegacyPassword,
            $userEmail,
            $userRank,
            $userDateRegistered,
            $userLastLogin,
            $userLastActive,
            $userTimeZone,
            $userRole,
            $userOfficeSymbol,
            $userBase,
            $userDisabled,
            $reminderSent
        );

        $rows = [];
        while ($stmt->fetch()) {
            $rows[] = [
                'uuid' => $uuid,
                'userFirstName' => $userFirstName,
                'userLastName' => $userLastName,
                'userHandle' => $userHandle,
                'userPassword' => $userPassword,
                'userLegacyPassword' => $userLegacyPassword,
                'userEmail' => $userEmail,
                'userRank' => $userRank,
                'userDateRegistered' => $userDateRegistered,
                'userLastLogin' => $userLastLogin,
                'userLastActive' => $userLastActive,
                'userTimeZone' => $userTimeZone,
                'userRole' => $userRole,
                'userOfficeSymbol' => $userOfficeSymbol,
                'userBase' => $userBase,
                'userDisabled' => $userDisabled,
                'reminderSent' => $reminderSent,
            ];
        }

        $stmt->close();
        return $this->create_objects($rows);
    }

    /**
     * @param User $user
     */
    public function save(User $user): void
    {
        if (empty($user->getUuid())) {
            return;
        }

        $uuid = $user->getUuid();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $handle = $user->getHandle();
        $password = $user->getPassword();
        $legacyPassword = $user->getLegacyPassword();
        $email = $user->getEmail();
        $rank = $user->getRank();
        $dateRegistered = $user->getDateRegistered()->format(
            DateTimeHelpers::DT_FMT_DB
        );
        $lastLogin = $user->getLastLogin()->format(
            DateTimeHelpers::DT_FMT_DB
        );
        $lastActive = $user->getLastActive()->format(
            DateTimeHelpers::DT_FMT_DB
        );
        $timeZone = $user->getTimeZone();
        $role = $user->getRole();
        $officeSymbol = $user->getOfficeSymbol();
        $base = $user->getBase();
        $disabled = (int)$user->isDisabled();
        $reminderSent = (int)$user->isReminderSent();

        $qry = <<<SQL
INSERT INTO userData
  (
    uuid, 
    userFirstName, 
    userLastName, 
    userHandle, 
    userPassword, 
    userLegacyPassword, 
    userEmail, 
    userRank, 
    userDateRegistered, 
    userLastLogin, 
    userLastActive, 
    userTimeZone, 
    userRole, 
    userOfficeSymbol, 
    userBase, 
    userDisabled,
    reminderSent
  )
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  userFirstName=VALUES(userFirstName),
  userLastName=VALUES(userLastName),
  userHandle=VALUES(userHandle),
  userPassword=VALUES(userPassword),
  userLegacyPassword=VALUES(userLegacyPassword),
  userEmail=VALUES(userEmail),
  userRank=VALUES(userRank),
  userDateRegistered=VALUES(userDateRegistered),
  userLastLogin=VALUES(userLastLogin),
  userLastActive=VALUES(userLastActive),
  userTimeZone=VALUES(userTimeZone),
  userRole=VALUES(userRole),
  userOfficeSymbol=VALUES(userOfficeSymbol),
  userBase=VALUES(userBase),
  userDisabled=VALUES(userDisabled),
  reminderSent=VALUES(reminderSent)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'sssssssssssssssii',
            $uuid,
            $firstName,
            $lastName,
            $handle,
            $password,
            $legacyPassword,
            $email,
            $rank,
            $dateRegistered,
            $lastLogin,
            $lastActive,
            $timeZone,
            $role,
            $officeSymbol,
            $base,
            $disabled,
            $reminderSent
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param User[] $users
     */
    public function saveArray(array $users): void
    {
        if (empty($users)) {
            return;
        }

        $c = count($users);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($users[ $i ])) {
                continue;
            }

            if (!$users[ $i ] instanceof User) {
                continue;
            }

            $this->save($users[ $i ]);
        }
    }
}