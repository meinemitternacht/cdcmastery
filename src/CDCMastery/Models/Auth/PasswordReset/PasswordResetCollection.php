<?php
declare(strict_types=1);


namespace CDCMastery\Models\Auth\PasswordReset;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\Users\User;
use DateTime;
use Monolog\Logger;
use mysqli;

class PasswordResetCollection
{
    private mysqli $db;
    private Logger $log;

    public function __construct(mysqli $db, Logger $log)
    {
        $this->db = $db;
        $this->log = $log;
    }

    /**
     * @param array $rows
     * @return PasswordReset[]
     */
    private function create_objects(array $rows): array
    {
        $resets = [];
        foreach ($rows as $row) {
            $initiated = DateTime::createFromFormat(DateTimeHelpers::DT_FMT_DB,
                                                    $row[ 'timeRequested' ],
                                                    DateTimeHelpers::utc_tz());
            $expires = DateTime::createFromFormat(DateTimeHelpers::DT_FMT_DB,
                                                  $row[ 'timeExpires' ],
                                                  DateTimeHelpers::utc_tz());

            $initiated->setTimezone(DateTimeHelpers::user_tz());
            $initiated->setTimezone(DateTimeHelpers::user_tz());

            $resets[ $row[ 'uuid' ] ] = new PasswordReset($row[ 'uuid' ],
                                                          $initiated,
                                                          $expires,
                                                          $row[ 'userUUID' ]);
        }

        return $resets;
    }

    public function fetch(string $uuid): ?PasswordReset
    {
        if (!$uuid) {
            return null;
        }

        $qry = <<<SQL
SELECT uuid, userUUID, timeRequested, timeExpires
FROM userPasswordResets
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

        $stmt->bind_result($_uuid, $user_uuid, $time_requested, $time_expires);
        $stmt->fetch();
        $stmt->close();

        if ($_uuid === null) {
            return null;
        }

        $row = [
            'uuid' => $_uuid,
            'userUUID' => $user_uuid,
            'timeRequested' => $time_requested,
            'timeExpires' => $time_expires,
        ];

        return $this->create_objects([$row])[ $uuid ] ?? null;
    }

    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT uuid, userUUID, timeRequested, timeExpires
FROM userPasswordResets
ORDER BY timeRequested DESC
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        if (!$rows) {
            return [];
        }

        $res->free();
        return $this->create_objects($rows);
    }

    public function fetchArray(array $uuids): array
    {
        if (!$uuids) {
            return [];
        }

        $uuids_str = implode("','",
                             array_map([$this->db, 'real_escape_string'],
                                       $uuids));

        $qry = <<<SQL
SELECT uuid, userUUID, timeRequested, timeExpires
FROM userPasswordResets
WHERE uuid IN ('{$uuids_str}')
ORDER BY timeRequested DESC
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        if (!$rows) {
            return [];
        }

        $res->free();
        return $this->create_objects($rows);
    }

    public function fetchByUser(User $user): ?PasswordReset
    {
        if (!$user->getUuid()) {
            return null;
        }

        $qry = <<<SQL
SELECT uuid, userUUID, timeRequested, timeExpires
FROM userPasswordResets
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return null;
        }

        $user_uuid = $user->getUuid();
        if (!$stmt->bind_param('s', $user_uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return null;
        }

        $stmt->bind_result($uuid, $_user_uuid, $time_requested, $time_expires);
        $stmt->fetch();
        $stmt->close();

        if ($uuid === null) {
            return null;
        }

        $row = [
            'uuid' => $uuid,
            'userUUID' => $_user_uuid,
            'timeRequested' => $time_requested,
            'timeExpires' => $time_expires,
        ];

        return $this->create_objects([$row])[ $uuid ] ?? null;
    }

    public function remove(PasswordReset $reset): void
    {
        if (!$reset->getUuid()) {
            return;
        }

        $qry = <<<SQL
DELETE FROM userPasswordResets WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        $uuid = $reset->getUuid();
        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    public function save(PasswordReset $reset): void
    {
        if (!$reset->getUuid()) {
            return;
        }

        $uuid = $reset->getUuid();
        $user_uuid = $reset->getUserUuid();
        $time_requested = $reset->getDateInitiated()
                                ->setTimezone(DateTimeHelpers::utc_tz())
                                ->format(DateTimeHelpers::DT_FMT_DB);
        $time_expires = $reset->getDateExpires()
                              ->setTimezone(DateTimeHelpers::utc_tz())
                              ->format(DateTimeHelpers::DT_FMT_DB);

        $qry = <<<SQL
INSERT INTO userPasswordResets
    (uuid, userUUID, timeRequested, timeExpires)
VALUES (?,?,?,?)
ON DUPLICATE KEY UPDATE 
    uuid=VALUES(uuid),
    userUUID=VALUES(userUUID),
    timeRequested=VALUES(timeRequested),
    timeExpires=VALUES(timeExpires)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ssss', $uuid, $user_uuid, $time_requested, $time_expires) ||
            !$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param PasswordReset[] $resets
     */
    public function saveArray(array $resets): void
    {
        foreach ($resets as $reset) {
            $this->save($reset);
        }
    }
}
