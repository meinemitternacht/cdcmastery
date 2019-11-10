<?php

namespace CDCMastery\Models\Bases;


use Monolog\Logger;
use mysqli;

class BaseCollection
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
     * BaseCollection constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param array $data
     * @return Base[]
     */
    private function create_objects(array $data): array
    {
        $bases = [];
        foreach ($data as $row) {
            $base = new Base();
            $base->setUuid($row['uuid']);
            $base->setName($row['baseName']);
            $bases[$row['uuid']] = $base;
        }

        return $bases;
    }

    /**
     * @param string $uuid
     * @return Base|null
     */
    public function fetch(string $uuid): ?Base
    {
        $base = null;

        if ($uuid === '') {
            goto out_return;
        }

        $qry = <<<SQL
SELECT
  uuid,
  baseName
FROM baseList
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            goto out_return;
        }

        if (!$stmt->bind_param('s', $uuid)) {
            $stmt->close();
            goto out_return;
        }

        if (!$stmt->execute()) {
            $stmt->close();
            goto out_return;
        }

        if (!$stmt->bind_result($_uuid,
                                $name)) {
            $stmt->close();
            goto out_return;
        }

        if (!$stmt->fetch()) {
            $stmt->close();
            goto out_return;
        }

        $stmt->close();
        $base = $this->create_objects([['uuid' => $_uuid, 'baseName' => $name]])[0] ?? null;

        out_return:
        return $base;
    }

    /**
     * @return Base[]
     */
    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT
  uuid,
  baseName
FROM baseList
ORDER BY baseName
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === '') {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();
        return $this->create_objects($rows);
    }

    /**
     * @param string[] $uuids
     * @return Base[]
     */
    public function fetchArray(array $uuids): array
    {
        if (count($uuids) === 0) {
            return [];
        }

        $uuids_str = implode("','",
                             array_map([$this->db, 'real_escape_string'],
                                       $uuids));

        $qry = <<<SQL
SELECT
  uuid,
  baseName
FROM baseList
WHERE uuid IN ('{$uuids_str}')
ORDER BY baseName
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $rows[] = $row;
        }

        $res->free();

        return array_intersect_key($this->create_objects($rows),
                                   array_flip($uuids));
    }

    /**
     * @param Base $base
     */
    public function save(Base $base): void
    {
        if (($base->getUuid() ?? '') === '') {
            return;
        }

        $uuid = $base->getUuid();
        $name = $base->getName();

        $qry = <<<SQL
INSERT INTO baseList
  (uuid, baseName)
VALUES (?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  baseName=VALUES(baseName)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return;
        }

        if (!$stmt->bind_param('ss',
                               $uuid,
                               $name)) {
            goto out_close;
        }

        $stmt->execute();

        out_close:
        $stmt->close();
    }

    /**
     * @param Base[] $bases
     */
    public function saveArray(array $bases): void
    {
        foreach ($bases as $base) {
            $this->save($base);
        }
    }
}