<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 4:07 PM
 */

namespace CDCMastery\Models\Bases;


use Monolog\Logger;

class BaseCollection
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
     * @var Base[]
     */
    private $bases = [];

    /**
     * BaseCollection constructor.
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
     * @return Base
     */
    public function fetch(string $uuid): Base
    {
        if (empty($uuid)) {
            return new Base();
        }

        $qry = <<<SQL
SELECT
  uuid,
  baseName
FROM baseList
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Base();
        }

        $stmt->bind_result(
            $_uuid,
            $name
        );

        $stmt->fetch();
        $stmt->close();

        $base = new Base();
        $base->setUuid($_uuid);
        $base->setName($name);

        $this->bases[$uuid] = $base;

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
ORDER BY baseName ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || empty($row['uuid'])) {
                continue;
            }

            $base = new Base();
            $base->setUuid($row['uuid'] ?? '');
            $base->setName($row['baseName'] ?? '');

            $this->bases[$row['uuid']] = $base;
        }

        $res->free();

        return $this->bases;
    }

    /**
     * @param string[] $uuidList
     * @return Base[]
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
  baseName
FROM baseList
WHERE uuid IN ('{$uuidListString}')
ORDER BY baseName ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $base = new Base();
            $base->setUuid($row['uuid'] ?? '');
            $base->setName($row['baseName'] ?? '');

            $this->bases[$row['uuid']] = $base;
        }

        $res->free();

        return array_intersect_key(
            $this->bases,
            array_flip($uuidList)
        );
    }

    /**
     * @return BaseCollection
     */
    public function reset(): self
    {
        $this->bases = [];

        return $this;
    }

    /**
     * @param Base $base
     */
    public function save(Base $base): void
    {
        if (empty($base->getUuid())) {
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
        $stmt->bind_param(
            'ss',
            $uuid,
            $name
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();

        $this->bases[$uuid] = $base;
    }

    /**
     * @param Base[] $bases
     */
    public function saveArray(array $bases): void
    {
        if (empty($bases)) {
            return;
        }

        $c = count($bases);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($bases[$i])) {
                continue;
            }

            if (!$bases[$i] instanceof Base) {
                continue;
            }

            $this->save($bases[$i]);
        }
    }
}