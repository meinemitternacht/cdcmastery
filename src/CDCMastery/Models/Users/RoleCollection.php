<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/27/2017
 * Time: 8:37 PM
 */

namespace CDCMastery\Models\Users;


use Monolog\Logger;

class RoleCollection
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
     * @var Role[]
     */
    private $roles = [];

    /**
     * RoleCollection constructor.
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param string $uuid
     * @return Role
     */
    public function fetch(string $uuid): Role
    {
        if (empty($uuid)) {
            return new Role();
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
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Role();
        }

        $stmt->bind_result(
            $_uuid,
            $type,
            $name,
            $description
        );

        $stmt->fetch();
        $stmt->close();

        $role = new Role();
        $role->setUuid($_uuid);
        $role->setType($type);
        $role->setName($name);
        $role->setDescription($description);

        $this->roles[$uuid] = $role;

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
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $role = new Role();
            $role->setUuid($row['uuid'] ?? '');
            $role->setType($row['roleType'] ?? '');
            $role->setName($row['roleName'] ?? '');
            $role->setDescription($row['roleDescription'] ?? '');

            $this->roles[$row['uuid']] = $role;
        }

        return $this->roles;
    }

    /**
     * @param string[] $uuidList
     * @return Role[]
     */
    public function fetchArray(array $uuidList): array
    {
        if (empty($uuidList)) {
            return [];
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);

        $qry = <<<SQL
SELECT
  uuid,
  roleType,
  roleName,
  roleDescription
FROM roleList
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $role = new Role();
            $role->setUuid($row['uuid'] ?? '');
            $role->setType($row['roleType'] ?? '');
            $role->setName($row['roleName'] ?? '');
            $role->setDescription($row['roleDescription'] ?? '');

            $this->roles[$row['uuid']] = $role;
        }

        return array_intersect_key(
            $this->roles,
            array_flip($uuidList)
        );
    }

    /**
     * @return RoleCollection
     */
    public function reset(): self
    {
        $this->roles = [];

        return $this;
    }

    /**
     * @param Role $role
     */
    public function save(Role $role): void
    {
        if (empty($role->getUuid())) {
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
        $stmt->bind_param(
            'ssss',
            $uuid,
            $type,
            $name,
            $description
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();

        $this->roles[$uuid] = $role;
    }

    /**
     * @param Role[] $roles
     */
    public function saveArray(array $roles): void
    {
        if (empty($roles)) {
            return;
        }

        $c = count($roles);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($roles[$i])) {
                continue;
            }

            if (!$roles[$i] instanceof Role) {
                continue;
            }

            $this->save($roles[$i]);
        }
    }
}