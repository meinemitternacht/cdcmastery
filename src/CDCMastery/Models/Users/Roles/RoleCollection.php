<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 8:37 PM
 */

namespace CDCMastery\Models\Users\Roles;


use CDCMastery\Helpers\DBLogHelper;
use Monolog\Logger;
use mysqli;

class RoleCollection
{
    protected mysqli $db;
    protected Logger $log;

    /**
     * RoleCollection constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    private function create_objects(array $data): array
    {
        $roles = [];
        foreach ($data as $row) {
            $role = new Role();
            $role->setUuid($row['uuid'] ?? '');
            $role->setType($row['roleType'] ?? '');
            $role->setName($row['roleName'] ?? '');
            $role->setDescription($row['roleDescription'] ?? '');
            $roles[ $row['uuid']] = $role;
        }

        return $roles;
    }

    /**
     * @param string $uuid
     * @return Role|null
     */
    public function fetch(string $uuid): ?Role
    {
        $role = null;
        if ($uuid === '') {
            goto out_return;
        }

        $qry = <<<SQL
SELECT
  uuid,
  roleType,
  roleName,
  roleDescription
FROM roleList
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            goto out_return;
        }

        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            goto out_return;
        }

        if (!$stmt->bind_result($_uuid,
                                $type,
                                $name,
                                $description)) {
            $stmt->close();
            goto out_return;
        }

        if (!$stmt->fetch()) {
            $stmt->close();
            goto out_return;
        }

        $stmt->close();

        $role = new Role();
        $role->setUuid($_uuid);
        $role->setType($type);
        $role->setName($name);
        $role->setDescription($description);

        out_return:
        return $role;
    }

    /**
     * @return Role[]
     */
    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT
  uuid,
  roleType,
  roleName,
  roleDescription
FROM roleList
ORDER BY uuid
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid'])) {
                continue;
            }

            $rows[] = $row;
        }

        return $this->create_objects($rows);
    }

    /**
     * @param string[] $uuids
     * @return Role[]
     */
    public function fetchArray(array $uuids): array
    {
        if (!$uuids) {
            return [];
        }

        $uuids_str = implode("','",
                             array_map([$this->db, 'real_escape_string'],
                                       $uuids));

        $qry = <<<SQL
SELECT
  uuid,
  roleType,
  roleName,
  roleDescription
FROM roleList
WHERE uuid IN ('{$uuids_str}')
ORDER BY uuid
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid'])) {
                continue;
            }

            $rows[] = $row;
        }

        return array_intersect_key($this->create_objects($rows),
                                   array_flip($uuids));
    }

    public function fetchType(string $type): ?Role
    {
        $role = null;

        $qry = <<<SQL
SELECT
  uuid,
  roleType,
  roleName,
  roleDescription
FROM roleList
WHERE roleType = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            goto out_return;
        }

        if (!$stmt->bind_param('s', $type) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            goto out_return;
        }

        if (!$stmt->bind_result($_uuid,
                                $type,
                                $name,
                                $description)) {
            $stmt->close();
            goto out_return;
        }

        if (!$stmt->fetch()) {
            $stmt->close();
            goto out_return;
        }

        $stmt->close();

        $role = new Role();
        $role->setUuid($_uuid);
        $role->setType($type);
        $role->setName($name);
        $role->setDescription($description);

        out_return:
        return $role;
    }

    /**
     * @param Role $role
     */
    private function _save(Role $role): void
    {
        if (($role->getUuid() ?? '') === '') {
            return;
        }

        $uuid = $role->getUuid();
        $type = $role->getType();
        $name = $role->getName();
        $description = $role->getDescription();

        $qry = <<<SQL
INSERT INTO roleList
  (
    uuid, 
    roleType, 
    roleName, 
    roleDescription
  )
VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  roleType=VALUES(roleType),
  roleName=VALUES(roleName),
  roleDescription=VALUES(roleDescription)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ssss',
                               $uuid,
                               $type,
                               $name,
                               $description) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param Role[] $roles
     */
    public function save(array $roles): void
    {
        foreach ($roles as $role) {
            $this->_save($role);
        }
    }
}