<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 8:18 PM
 */

namespace CDCMastery\Models\CdcData;


use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Helpers\UUID;
use Monolog\Logger;
use mysqli;
use RuntimeException;

class AfscCollection
{
    public const COL_UUID = 'uuid';
    public const COL_NAME = 'name';
    public const COL_DESCRIPTION = 'description';
    public const COL_VERSION = 'version';
    public const COL_EDIT_CODE = 'editCode';
    public const COL_IS_FOUO = 'fouo';
    public const COL_IS_HIDDEN = 'hidden';
    public const COL_IS_OBSOLETE = 'obsolete';

    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    public const SHOW_ALL = PHP_INT_MAX;
    public const SHOW_HIDDEN = 1 << 0;
    public const SHOW_FOUO = 1 << 1;
    public const SHOW_OBSOLETE = 1 << 2;

    private const TABLE_NAME = 'afscList';
    private const DEFAULT_COL = self::COL_NAME;
    private const DEFAULT_ORDER = self::ORDER_ASC;

    protected mysqli $db;
    protected Logger $log;

    /**
     * AfscCollection constructor.
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

            $afsc = new Afsc();
            $afsc->setUuid($row[ 'uuid' ]);
            $afsc->setName($row[ 'name' ]);
            $afsc->setDescription($row[ 'description' ]);
            $afsc->setVersion($row[ 'version' ]);
            $afsc->setEditCode($row[ 'editCode' ]);
            $afsc->setFouo((bool)$row[ 'fouo' ]);
            $afsc->setHidden((bool)$row[ 'hidden' ]);
            $afsc->setObsolete((bool)$row[ 'obsolete' ]);

            $out[ $row[ 'uuid' ] ] = $afsc;
        }

        return $out;
    }

    public function delete(Afsc $afsc): void
    {
        if (!CDC_DEBUG) {
            throw new RuntimeException('Cannot delete AFSC when running in production mode');
        }

        if ($afsc->getUuid() === '') {
            return;
        }

        $qry = <<<SQL
DELETE FROM afscList
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            goto out_error;
        }

        $uuid = $afsc->getUuid();

        if (!$stmt->bind_param('s', $uuid) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            goto out_error;
        }

        $stmt->close();
        return;

        out_error:
        throw new RuntimeException("Unable to remove AFSC {$afsc->getUuid()}");
    }

    public function exists(Afsc $afsc): bool
    {
        if ($afsc->getUuid() !== '') {
            $afsc = $this->fetch($afsc->getUuid());

            if (!$afsc) {
                return false;
            }

            return $afsc->getUuid() !== '';
        }

        if ($afsc->getName() === '') {
            return false;
        }

        $qry = <<<SQL
SELECT 1 FROM afscList
WHERE name = ?
  AND editCode = ?
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return false;
        }

        $name = $afsc->getName();
        $editcode = $afsc->getEditCode();

        if (!$stmt->bind_param('ss', $name, $editcode) ||
            !$stmt->execute() ||
            !$stmt->bind_result($res) ||
            !$stmt->fetch()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return false;
        }

        $stmt->close();
        return (bool)$res;
    }

    /**
     * @param array $columnOrders
     * @return string
     */
    private static function generateOrderSuffix(array $columnOrders): string
    {
        if (!$columnOrders) {
            return self::generateOrderSuffix([self::DEFAULT_COL => self::DEFAULT_ORDER]);
        }

        $qry = [];
        foreach ($columnOrders as $column => $order) {
            switch ($column) {
                case self::COL_UUID:
                case self::COL_NAME:
                case self::COL_DESCRIPTION:
                case self::COL_VERSION:
                case self::COL_EDIT_CODE:
                case self::COL_IS_FOUO:
                case self::COL_IS_HIDDEN:
                case self::COL_IS_OBSOLETE:
                    $str = self::TABLE_NAME . '.' . $column;
                    break;
                default:
                    continue 2;
            }

            switch ($order) {
                case self::ORDER_ASC:
                    $str .= ' ASC';
                    break;
                case self::ORDER_DESC:
                default:
                    $str .= ' DESC';
                    break;
            }

            $qry[] = $str;
        }

        return ' ORDER BY ' . implode(' , ', $qry);
    }

    /**
     * @param int $flags
     * @param bool $omitWhere
     * @return string
     */
    private static function generateWhereSuffix(int $flags, bool $omitWhere = false): string
    {
        $showFouo = ($flags & self::SHOW_FOUO) !== 0;
        $showHidden = ($flags & self::SHOW_HIDDEN) !== 0;
        $showObsolete = ($flags & self::SHOW_OBSOLETE) !== 0;

        $qry = '';
        $parts = [];
        if ($showFouo && $showHidden && $showObsolete) {
            goto out_query;
        }

        $qry = $omitWhere
            ? ' AND '
            : ' WHERE ';

        if (!$showFouo) {
            $parts[] = self::COL_IS_FOUO . ' = 0';
        }

        if (!$showHidden) {
            $parts[] = self::COL_IS_HIDDEN . ' = 0';
        }

        if (!$showObsolete) {
            $parts[] = self::COL_IS_OBSOLETE . ' = 0';
        }

        out_query:
        $qry .= implode(' AND ', $parts);

        return $qry ?? '';
    }

    public function fetch(string $uuid): ?Afsc
    {
        if (!$uuid) {
            return null;
        }

        $qry = <<<SQL
SELECT
  uuid,
  name,
  description,
  version,
  editCode,
  fouo,
  hidden,
  obsolete
FROM afscList
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
            $uuid2,
            $name,
            $description,
            $version,
            $editCode,
            $fouo,
            $hidden,
            $obsolete
        );

        $stmt->fetch();
        $stmt->close();

        if (!$uuid2) {
            return null;
        }

        $row = [
            'uuid' => $uuid2,
            'name' => $name,
            'description' => $description,
            'version' => $version,
            'editCode' => $editCode,
            'fouo' => $fouo,
            'hidden' => $hidden,
            'obsolete' => $obsolete,
        ];

        return $this->create_objects([$row])[ $uuid ] ?? null;
    }

    /**
     * @param int $flags
     * @param array $columnOrders
     * @return Afsc[]
     */
    public function fetchAll(int $flags = self::SHOW_FOUO, array $columnOrders = []): array
    {
        $qry = <<<SQL
SELECT
  uuid,
  name,
  description,
  version,
  editCode,
  fouo,
  hidden,
  obsolete
FROM afscList
SQL;

        $qry .= self::generateWhereSuffix($flags);
        $qry .= self::generateOrderSuffix($columnOrders);

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
     * @param array $uuidList
     * @param array $columnOrders
     * @return Afsc[]
     */
    public function fetchArray(array $uuidList, array $columnOrders = []): array
    {
        if (!$uuidList) {
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
  name,
  description,
  version,
  editCode,
  fouo,
  hidden,
  obsolete
FROM afscList
WHERE uuid IN ('{$uuidListString}')
SQL;

        $qry .= self::generateOrderSuffix($columnOrders);

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
     * @param Afsc $afsc
     * @return bool
     */
    public function save(Afsc $afsc): bool
    {
        if (!$afsc->getUuid()) {
            $afsc->setUuid(UUID::generate());
        }

        $uuid = $afsc->getUuid();
        $name = $afsc->getName();
        $description = $afsc->getDescription();
        $version = $afsc->getVersion();
        $edit_code = $afsc->getEditCode();
        $fouo = $afsc->isFouo();
        $hidden = $afsc->isHidden();
        $obsolete = $afsc->isObsolete();

        $qry = <<<SQL
INSERT INTO afscList
  (
    uuid,
    name,
    description,
    version,
    editCode,
    fouo,
    hidden,
    obsolete
  ) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  name=VALUES(name),
  description=VALUES(description),
  version=VALUES(version),
  editCode=VALUES(editCode),
  fouo=VALUES(fouo),
  hidden=VALUES(hidden),
  obsolete=VALUES(obsolete)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return false;
        }

        if (!$stmt->bind_param('sssssiii',
                               $uuid,
                               $name,
                               $description,
                               $version,
                               $edit_code,
                               $fouo,
                               $hidden,
                               $obsolete) ||
            !$stmt->execute()) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $stmt);
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;
    }

    /**
     * @param array $afscs
     */
    public function saveArray(array $afscs): void
    {
        if (!$afscs) {
            return;
        }

        foreach ($afscs as $afsc) {
            if (!$afsc instanceof Afsc) {
                continue;
            }

            $this->save($afsc);
        }
    }
}
