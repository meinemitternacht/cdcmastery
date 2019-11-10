<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 8:18 PM
 */

namespace CDCMastery\Models\CdcData;


use CDCMastery\Helpers\UUID;
use Monolog\Logger;
use mysqli;
use function count;

class AfscCollection
{
    public const COL_UUID = 'uuid';
    public const COL_NAME = 'name';
    public const COL_DESCRIPTION = 'description';
    public const COL_VERSION = 'version';
    public const COL_IS_FOUO = 'fouo';
    public const COL_IS_HIDDEN = 'hidden';
    public const COL_IS_OBSOLETE = 'obsolete';

    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    public const SHOW_HIDDEN = 1 << 0;
    public const SHOW_FOUO = 1 << 1;
    public const SHOW_OBSOLETE = 1 << 2;

    private const TABLE_NAME = 'afscList';
    private const DEFAULT_COL = self::COL_NAME;
    private const DEFAULT_ORDER = self::ORDER_ASC;

    /**
     * @var mysqli
     */
    protected $db;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var Afsc[]
     */
    private $afscs = [];

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

    public function exists(Afsc $afsc): bool
    {
        if (($afsc->getUuid() ?? '') !== '') {
            return ($this->fetch($afsc->getUuid())->getUuid() ?? '') !== '';
        }

        if ($afsc->getName() === '') {
            return false;
        }

        $sql = <<<SQL
SELECT 1 FROM afscList
WHERE name = ?
SQL;

        $stmt = $this->db->prepare($sql);

        if ($stmt === false) {
            return false;
        }

        $name = $afsc->getName();

        if (!$stmt->bind_param('s', $name)) {
            $stmt->close();
            return false;
        }

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        if (!$stmt->bind_result($res)) {
            $stmt->close();
            return false;
        }

        if (!$stmt->fetch()) {
            $stmt->close();
            return false;
        }

        $stmt->close();

        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * @param array $columnOrders
     * @return string
     */
    private static function generateOrderSuffix(array $columnOrders): string
    {
        if (count($columnOrders) === 0) {
            return self::generateOrderSuffix([self::DEFAULT_COL => self::DEFAULT_ORDER]);
        }

        $sql = [];
        foreach ($columnOrders as $column => $order) {
            switch ($column) {
                case self::COL_UUID:
                case self::COL_NAME:
                case self::COL_DESCRIPTION:
                case self::COL_VERSION:
                case self::COL_IS_FOUO:
                case self::COL_IS_HIDDEN:
                    $str = self::TABLE_NAME . '.' . $column;
                    break;
                default:
                    $str = '';
                    continue;
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

            $sql[] = $str;
        }

        return ' ORDER BY ' . implode(' , ', $sql);
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
        $qry = $qry . implode(' AND ', $parts);

        return $qry ?? '';
    }

    /**
     * @param string $uuid
     * @return Afsc
     */
    public function fetch(string $uuid): Afsc
    {
        if (empty($uuid)) {
            return new Afsc();
        }

        $qry = <<<SQL
SELECT
  uuid,
  name,
  description,
  version,
  fouo,
  hidden,
  obsolete
FROM afscList
WHERE uuid = ?
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param('s', $uuid);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Afsc();
        }

        $stmt->bind_result(
            $_uuid,
            $name,
            $description,
            $version,
            $fouo,
            $hidden,
            $obsolete
        );

        $stmt->fetch();
        $stmt->close();

        $afsc = new Afsc();
        $afsc->setUuid($_uuid);
        $afsc->setName($name);
        $afsc->setDescription($description);
        $afsc->setVersion($version);
        $afsc->setFouo((bool)$fouo);
        $afsc->setHidden((bool)$hidden);
        $afsc->setObsolete((bool)$obsolete);

        $this->afscs[$uuid] = $afsc;

        return $afsc;
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
  fouo,
  hidden,
  obsolete
FROM afscList
SQL;

        $qry .= self::generateWhereSuffix($flags);
        $qry .= self::generateOrderSuffix($columnOrders);

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $afsc = new Afsc();
            $afsc->setUuid($row['uuid'] ?? '');
            $afsc->setName($row['name'] ?? '');
            $afsc->setDescription($row['description'] ?? '');
            $afsc->setVersion($row['version'] ?? '');
            $afsc->setFouo((bool)($row['fouo'] ?? false));
            $afsc->setHidden((bool)($row['hidden'] ?? false));
            $afsc->setObsolete((bool)($row['obsolete'] ?? false));

            $this->afscs[$row['uuid']] = $afsc;
        }

        $res->free();

        return $this->afscs;
    }

    /**
     * @param array $uuidList
     * @param array $columnOrders
     * @return array
     */
    public function fetchArray(array $uuidList, array $columnOrders = []): array
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
  name,
  description,
  version,
  fouo,
  hidden,
  obsolete
FROM afscList
WHERE uuid IN ('{$uuidListString}')
SQL;

        $qry .= self::generateOrderSuffix($columnOrders);

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || $row['uuid'] === null) {
                continue;
            }

            $afsc = new Afsc();
            $afsc->setUuid($row['uuid'] ?? '');
            $afsc->setName($row['name'] ?? '');
            $afsc->setDescription($row['description'] ?? '');
            $afsc->setVersion($row['version'] ?? '');
            $afsc->setFouo((bool)$row['fouo'] ?? false);
            $afsc->setHidden((bool)$row['hidden'] ?? false);
            $afsc->setObsolete((bool)($row['obsolete'] ?? false));

            $this->afscs[$row['uuid']] = $afsc;
        }

        $res->free();

        return array_intersect_key(
            $this->afscs,
            array_flip($uuidList)
        );
    }

    /**
     * @param Afsc $afsc
     * @return bool
     */
    public function save(Afsc $afsc): bool
    {
        if (empty($afsc->getUuid())) {
            $afsc->setUuid(UUID::generate());
        }

        $uuid = $afsc->getUuid();
        $name = $afsc->getName();
        $description = $afsc->getDescription();
        $version = $afsc->getVersion();
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
    fouo, 
    hidden,
    obsolete
  ) 
VALUES (?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  name=VALUES(name),
  description=VALUES(description),
  version=VALUES(version),
  fouo=VALUES(fouo),
  hidden=VALUES(hidden),
  obsolete=VALUES(obsolete)
SQL;

        $stmt = $this->db->prepare($qry);

        if ($stmt === false) {
            return false;
        }

        if (!$stmt->bind_param('ssssiii',
                               $uuid,
                               $name,
                               $description,
                               $version,
                               $fouo,
                               $hidden,
                               $obsolete)) {
            $stmt->close();
            return false;
        }

        if (!$stmt->execute()) {
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
        if (empty($afscs)) {
            return;
        }

        $c = count($afscs);
        for ($i = 0; $i < $c; $i++) {
            if (!isset($afscs[$i])) {
                continue;
            }

            if (!$afscs[$i] instanceof Afsc) {
                continue;
            }

            $this->save($afscs[$i]);
        }
    }
}