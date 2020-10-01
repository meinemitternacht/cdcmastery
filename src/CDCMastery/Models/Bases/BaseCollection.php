<?php

namespace CDCMastery\Models\Bases;


use CDCMastery\Helpers\DBLogHelper;
use Monolog\Logger;
use mysqli;

class BaseCollection
{
    protected mysqli $db;
    protected Logger $log;

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
            $base->setUuid($row[ 'uuid' ]);
            $base->setName($row[ 'baseName' ]);
            $bases[ $row[ 'uuid' ] ] = $base;
        }

        $this->fetch_aggregate_data($bases);
        return $bases;
    }

    private function fetch_aggregate_users(array $bases): void
    {
        $qry = <<<SQL
SELECT
    COUNT(*) AS count,
    baseList.uuid AS uuid
FROM userData
LEFT JOIN baseList ON userData.userBase = baseList.uuid
GROUP BY userData.userBase
ORDER BY userData.userBase
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        while ($row = $res->fetch_assoc()) {
            if (!isset($bases[ $row[ 'uuid' ] ])) {
                continue;
            }

            $bases[ $row[ 'uuid' ] ]->setUsers((int)$row[ 'count' ]);
        }

        $res->free();
    }

    /**
     * @param Base[] $bases
     */
    private function fetch_aggregate_tests(array $bases): void
    {
        $qry = <<<SQL
SELECT
    COUNT(*) AS count,
    userData.userBase AS uuid,
    1 AS completed
FROM testCollection
LEFT JOIN userData ON testCollection.userUuid = userData.uuid
WHERE testCollection.timeCompleted IS NOT NULL
GROUP BY userData.userBase
UNION ALL
(
    SELECT COUNT(*)          AS count,
           userData.userBase AS uuid,
           0 AS completed
    FROM testCollection
    LEFT JOIN userData ON testCollection.userUuid = userData.uuid
    WHERE testCollection.timeCompleted IS NULL
    GROUP BY userData.userBase
)
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        while ($row = $res->fetch_assoc()) {
            if (!isset($bases[ $row[ 'uuid' ] ])) {
                continue;
            }

            $row[ 'completed' ]
                ? $bases[ $row[ 'uuid' ] ]->setTestsComplete((int)$row[ 'count' ])
                : $bases[ $row[ 'uuid' ] ]->setTestsIncomplete((int)$row[ 'count' ]);
        }

        $res->free();
    }

    /**
     * @param Base[] $bases
     */
    private function fetch_aggregate_data(array $bases): void
    {
        if (!$bases) {
            return;
        }

        $this->fetch_aggregate_users($bases);
        $this->fetch_aggregate_tests($bases);
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
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            goto out_return;
        }

        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute() ||
            !$stmt->bind_result($_uuid, $name) ||
            !$stmt->fetch()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            goto out_return;
        }

        $stmt->close();
        $base = $this->create_objects([['uuid' => $_uuid, 'baseName' => $name]])[ $uuid ] ?? null;

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
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            if (!isset($row[ 'uuid' ]) || $row[ 'uuid' ] === '') {
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
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ss', $uuid, $name) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            goto out_close;
        }

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