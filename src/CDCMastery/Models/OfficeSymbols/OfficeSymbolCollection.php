<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 9:54 AM
 */

namespace CDCMastery\Models\OfficeSymbols;


use Monolog\Logger;

class OfficeSymbolCollection
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
     * @var OfficeSymbol[]
     */
    private $symbols = [];

    /**
     * OfficeSymbolCollection constructor.
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
     */
    public function delete(string $uuid): void
    {
        if (empty($uuid)) {
            return;
        }

        $uuid = $this->db->real_escape_string($uuid);

        $qry = <<<SQL
DELETE FROM officeSymbolList
WHERE uuid = '{$uuid}'
SQL;

        $this->db->query($qry);
    }

    /**
     * @param string[] $uuidList
     */
    public function deleteArray(array $uuidList): void
    {
        if (empty($uuidList)) {
            return;
        }

        $uuidListFiltered = array_map(
            [$this->db, 'real_escape_string'],
            $uuidList
        );

        $uuidListString = implode("','", $uuidListFiltered);

        $qry = <<<SQL
DELETE FROM officeSymbolList
WHERE uuid IN ('{$uuidListString}')
SQL;

        $this->db->query($qry);
    }

    /**
     * @param string $uuid
     * @return OfficeSymbol
     */
    public function fetch(string $uuid): OfficeSymbol
    {
        if (empty($uuid)) {
            return new OfficeSymbol();
        }

        $qry = <<<SQL
SELECT
  uuid,
  officeSymbol
FROM officeSymbolList
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new OfficeSymbol();
        }

        $stmt->bind_result(
            $_uuid,
            $symbol
        );

        $stmt->fetch();
        $stmt->close();

        if (!isset($_uuid) || $_uuid === null) {
            return new OfficeSymbol();
        }

        $os = new OfficeSymbol();
        $os->setUuid($_uuid ?? '');
        $os->setSymbol($symbol ?? '');

        $this->symbols[$_uuid] = $os;

        return $os;
    }

    /**
     * @return OfficeSymbol[]
     */
    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT
 uuid,
 officeSymbol
FROM officeSymbolList
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $os = new OfficeSymbol();
            $os->setUuid($row['uuid'] ?? '');
            $os->setSymbol($row['officeSymbol'] ?? '');

            $this->symbols[$row['uuid']] = $os;
        }

        $res->free();

        return $this->symbols;
    }

    /**
     * @param string[] $uuidList
     * @return OfficeSymbol[]
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
 officeSymbol
FROM officeSymbolList
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $os = new OfficeSymbol();
            $os->setUuid($row['uuid'] ?? '');
            $os->setSymbol($row['officeSymbol'] ?? '');

            $this->symbols[$row['uuid']] = $os;
        }

        $res->free();

        return array_intersect_key(
            $this->symbols,
            array_flip($uuidList)
        );
    }

    /**
     * @return OfficeSymbolCollection
     */
    public function refresh(): self
    {
        $this->symbols = [];

        return $this;
    }

    /**
     * @param OfficeSymbol $officeSymbol
     */
    public function save(OfficeSymbol $officeSymbol): void
    {
        if (empty($officeSymbol->getUuid())) {
            return;
        }

        $uuid = $officeSymbol->getUuid();
        $symbol = $officeSymbol->getSymbol();

        $qry = <<<SQL
INSERT INTO officeSymbolList
  (
    uuid,
    officeSymbol
  ) 
VALUES (?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  officeSymbol=VALUES(officeSymbol)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ss',
            $uuid,
            $symbol
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
    }

    /**
     * @param OfficeSymbol[] $officeSymbols
     */
    public function saveArray(array $officeSymbols): void
    {
        if (empty($officeSymbols)) {
            return;
        }

        $values = [];
        $c = count($officeSymbols);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($officeSymbols[$i])) {
                continue;
            }

            if (!$officeSymbols[$i] instanceof OfficeSymbol) {
                continue;
            }

            $values[] = "('" . $officeSymbols[$i]->getUuid() . "','" . $officeSymbols[$i]->getSymbol() . "')";
        }

        if (empty($values)) {
            return;
        }

        $values = implode(',', $values);

        $qry = <<<SQL
INSERT INTO officeSymbolList
  (
    uuid,
    officeSymbol
  ) 
VALUES {$values}
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  officeSymbol=VALUES(officeSymbol)
SQL;

        $this->db->query($qry);
    }
}