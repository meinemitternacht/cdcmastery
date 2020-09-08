<?php


namespace CDCMastery\Models\Users\Roles;


use CDCMastery\Helpers\DateTimeHelpers;
use DateTime;
use Monolog\Logger;
use mysqli;

class PendingRoleCollection
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
     * @return PendingRole[]
     */
    private function create_objects(array $rows): array
    {
        $roles = [];
        foreach ($rows as $row) {
            $roles[ $row[ 'userUUID' ] ] = new PendingRole($row[ 'userUUID' ],
                                                           $row[ 'roleUUID' ],
                                                           DateTime::createFromFormat(DateTimeHelpers::DT_FMT_DB,
                                                                                      $row[ 'dateRequested' ]));
        }

        return $roles;
    }

    public function count(): int
    {
        $qry = <<<SQL
SELECT COUNT(*) AS count FROM queueRoleAuthorization
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return 0;
        }

        $row = $res->fetch_assoc();
        $res->free();

        return (int)($row[ 'count' ] ?? 0);
    }

    public function fetch(string $user_uuid): ?PendingRole
    {
        if (!$user_uuid) {
            return null;
        }

        $qry = <<<SQL
SELECT userUUID, roleUUID, dateRequested
FROM queueRoleAuthorization
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return null;
        }

        if (!$stmt->bind_param('s', $user_uuid) ||
            !$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $stmt->bind_result($_user_uuid, $role_uuid, $date_requested);
        $stmt->fetch();
        $row = [
            'userUUID' => $_user_uuid,
            'roleUUID' => $role_uuid,
            'dateRequested' => $date_requested,
        ];

        $stmt->close();
        return $this->create_objects([$row])[ $user_uuid ] ?? null;
    }

    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT userUUID, roleUUID, dateRequested
FROM queueRoleAuthorization
ORDER BY dateRequested DESC
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return [];
        }

        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $res->free();

        return $this->create_objects($rows);
    }

    public function fetchArray(array $user_uuids): array
    {
        if (!$user_uuids) {
            return [];
        }

        $user_uuids_str = implode("','",
                                  array_map([$this->db, 'real_escape_string'],
                                            $user_uuids));

        $qry = <<<SQL
SELECT userUUID, roleUUID, dateRequested
FROM queueRoleAuthorization
WHERE userUUID IN ('{$user_uuids_str}')
ORDER BY dateRequested DESC
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

        $stmt->bind_result($_user_uuid, $role_uuid, $date_requested);

        $rows = [];
        while ($stmt->fetch()) {
            $rows[] = [
                'userUUID' => $_user_uuid,
                'roleUUID' => $role_uuid,
                'dateRequested' => $date_requested,
            ];
        }

        $stmt->close();
        return $this->create_objects($rows);
    }

    public function remove(PendingRole $role): void
    {
        $qry = <<<SQL
DELETE FROM queueRoleAuthorization
WHERE userUUID = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return;
        }

        $user_uuid = $role->getUserUuid();

        if (!$stmt->bind_param('s', $user_uuid)) {
            $stmt->close();
            return;
        }

        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param PendingRole[] $roles
     */
    public function removeArray(array $roles): void
    {
        foreach ($roles as $role) {
            $this->remove($role);
        }
    }

    public function save(PendingRole $role): void
    {
        $qry = <<<SQL
INSERT INTO queueRoleAuthorization
    (userUUID, roleUUID, dateRequested)
VALUES (?,?,?)
ON DUPLICATE KEY UPDATE
    userUUID=VALUES(userUUID),
    roleUUID=VALUES(roleUUID),
    dateRequested=VALUES(dateRequested)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return;
        }

        $user_uuid = $role->getUserUuid();
        $role_uuid = $role->getRoleUuid();
        $date_requested = $role->getDateRequested()->format(DateTimeHelpers::DT_FMT_DB);

        if (!$stmt->bind_param('sss', $user_uuid, $role_uuid, $date_requested)) {
            $stmt->close();
            return;
        }

        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param PendingRole[] $roles
     */
    public function saveArray(array $roles): void
    {
        foreach ($roles as $role) {
            $this->save($role);
        }
    }
}