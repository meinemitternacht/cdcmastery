<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 8:07 PM
 */

namespace CDCMastery\Models\Users;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\Sorting\ISortOption;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use DateTime;
use Monolog\Logger;
use mysqli;

class UserCollection
{
    protected mysqli $db;
    protected Logger $log;

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
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $user = new User();
            $user->setUuid($row[ 'uuid' ] ?? '');
            $user->setFirstName($row[ 'userFirstName' ] ?? '');
            $user->setLastName($row[ 'userLastName' ] ?? '');
            $user->setHandle($row[ 'userHandle' ] ?? '');
            $user->setPassword($row[ 'userPassword' ] ?? '');
            $user->setLegacyPassword($row[ 'userLegacyPassword' ] ?? '');
            $user->setEmail($row[ 'userEmail' ] ?? '');
            $user->setRank($row[ 'userRank' ] ?? '');

            $user->setDateRegistered(null);
            if (($row[ 'userDateRegistered' ] ?? null) !== null) {
                $dt_obj = DateTime::createFromFormat(
                    DateTimeHelpers::DT_FMT_DB,
                    $row[ 'userDateRegistered' ] ?? '',
                    DateTimeHelpers::utc_tz()
                );
                $dt_obj->setTimezone(DateTimeHelpers::user_tz());
                $user->setDateRegistered($dt_obj);
            }

            $user->setLastLogin(null);
            if (($row[ 'userLastLogin' ] ?? null) !== null) {
                $dt_obj = DateTime::createFromFormat(
                    DateTimeHelpers::DT_FMT_DB,
                    $row[ 'userLastLogin' ] ?? '',
                    DateTimeHelpers::utc_tz()
                );
                $dt_obj->setTimezone(DateTimeHelpers::user_tz());
                $user->setLastLogin($dt_obj);
            }

            $user->setLastActive(null);
            if (($row[ 'userLastActive' ] ?? null) !== null) {
                $dt_obj = DateTime::createFromFormat(
                    DateTimeHelpers::DT_FMT_DB,
                    $row[ 'userLastActive' ] ?? '',
                    DateTimeHelpers::utc_tz()
                );
                $dt_obj->setTimezone(DateTimeHelpers::user_tz());
                $user->setLastActive($dt_obj);
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
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return 0;
        }

        $row = $res->fetch_assoc();
        $res->free();

        return (int)$row[ 'count' ];
    }

    public function delete(User $user): void
    {
        $uuid = $user->getUuid();

        if (!$uuid) {
            return;
        }

        $qry = <<<SQL
DELETE FROM userData
WHERE uuid = '{$uuid}'
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }
    }

    /**
     * @param User[] $users
     */
    public function deleteArray(array $users): void
    {
        foreach ($users as $user) {
            $this->delete($user);
        }
    }

    public function fetch(string $uuid): ?User
    {
        if (empty($uuid)) {
            return null;
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return null;
        }

        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return null;
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

        if (!DateTimeHelpers::user_tz_set()) {
            DateTimeHelpers::set_user_tz($timeZone);
        }

        return $this->create_objects([$row])[ $_uuid ] ?? null;
    }

    /**
     * @param ISortOption[]|null $sort_options
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

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();

        return $this->create_objects($rows);
    }

    /**
     * @param string[] $uuidList
     * @param ISortOption[]|null $sort_options
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

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();

        return $this->create_objects($rows);
    }

    // @todo proper filter function for each column
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
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $base_uuid = $base->getUuid();
        if (!$stmt->bind_param('s', $base_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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

        $date_registered = $user->getDateRegistered() ?? new DateTime();
        $date_last_login = $user->getLastLogin();
        $date_last_active = $user->getLastActive();

        $uuid = $user->getUuid();
        $firstName = $user->getFirstName();
        $lastName = $user->getLastName();
        $handle = $user->getHandle();
        $password = $user->getPassword();
        $legacyPassword = $user->getLegacyPassword();
        $email = $user->getEmail();
        $rank = $user->getRank();
        $dateRegistered = $date_registered->setTimezone(DateTimeHelpers::utc_tz())
                                          ->format(DateTimeHelpers::DT_FMT_DB);
        $lastLogin = $date_last_login instanceof DateTime
            ? $date_last_login->setTimezone(DateTimeHelpers::utc_tz())
                              ->format(DateTimeHelpers::DT_FMT_DB)
            : null;
        $lastActive = $date_last_active instanceof DateTime
            ? $date_last_active->setTimezone(DateTimeHelpers::utc_tz())
                               ->format(DateTimeHelpers::DT_FMT_DB)
            : null;
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('sssssssssssssssii',
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
                               $reminderSent) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $this->save($user);
        }
    }

    public function search(string $term): array
    {
        $term = $this->db->real_escape_string($term);

        $clauses = [
            "userData.uuid LIKE '{$term}%'",
            "userData.userFirstName LIKE '%{$term}%'",
            "userData.userEmail LIKE '%{$term}%'",
            "userData.userHandle LIKE '%{$term}%'",
        ];

        $clause_str = implode(' OR ', $clauses);

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
WHERE {$clause_str}
ORDER BY userData.userLastName,
         userData.userFirstName,
         userData.userRank
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ])) {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();

        return $this->create_objects($rows);
    }
}