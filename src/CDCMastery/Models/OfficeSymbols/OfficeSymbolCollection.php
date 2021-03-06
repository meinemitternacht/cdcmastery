<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/2/2017
 * Time: 9:54 AM
 */

namespace CDCMastery\Models\OfficeSymbols;


use CDCMastery\Helpers\DBLogHelper;
use Monolog\Logger;
use mysqli;

class OfficeSymbolCollection
{
    protected mysqli $db;
    protected Logger $log;

    /**
     * OfficeSymbolCollection constructor.
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
     * @return OfficeSymbol[]
     */
    private function create_objects(array $data): array
    {
        if (!$data) {
            return [];
        }

        $symbols = [];
        foreach ($data as $row) {
            $symbol = new OfficeSymbol();
            $symbol->setUuid($row[ 'uuid' ]);
            $symbol->setSymbol($row[ 'officeSymbol' ]);

            $symbols[ $row[ 'uuid' ] ] = $symbol;
        }

        $this->fetch_aggregate_data($symbols);
        return $symbols;
    }

    /**
     * @param OfficeSymbol[] $symbols
     */
    private function fetch_aggregate_data(array $symbols): void
    {
        if (!$symbols) {
            return;
        }

        $qry = <<<SQL
SELECT
    COUNT(*) AS count,
    officeSymbolList.uuid AS uuid
FROM userData
LEFT JOIN officeSymbolList
    ON userData.userOfficeSymbol = officeSymbolList.uuid
GROUP BY userData.userOfficeSymbol
ORDER BY userData.userOfficeSymbol
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        while ($row = $res->fetch_assoc()) {
            if (!isset($symbols[ $row[ 'uuid' ] ])) {
                continue;
            }

            $symbols[ $row[ 'uuid' ] ]->setUsers((int)$row[ 'count' ]);
        }

        $res->free();
    }

    public function delete(OfficeSymbol $osymbol): void
    {
        if ($osymbol->getUuid() === '') {
            return;
        }

        $uuid = $this->db->real_escape_string($osymbol->getUuid());

        $qry = <<<SQL
DELETE FROM officeSymbolList
WHERE uuid = '{$uuid}'
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }
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

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }
    }

    /**
     * @param string $uuid
     * @return OfficeSymbol|null
     */
    public function fetch(string $uuid): ?OfficeSymbol
    {
        if (!$uuid) {
            return null;
        }

        $qry = <<<SQL
SELECT
  uuid,
  officeSymbol
FROM officeSymbolList
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
            $symbol
        );

        $stmt->fetch();
        $stmt->close();

        if (!isset($_uuid)) {
            return null;
        }

        $rows[] = [
            'uuid' => $_uuid,
            'officeSymbol' => $symbol,
        ];

        return $this->create_objects($rows)[ $uuid ] ?? null;
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
ORDER BY officeSymbol
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
ORDER BY uuid
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

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return;
        }

        if (!$stmt->bind_param('ss',
                               $uuid,
                               $symbol) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
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
        foreach ($officeSymbols as $officeSymbol) {
            if (!$officeSymbol instanceof OfficeSymbol) {
                continue;
            }

            $values[] = "('" . $officeSymbol->getUuid() . "','" . $officeSymbol->getSymbol() . "')";
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

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
        }
    }
}
