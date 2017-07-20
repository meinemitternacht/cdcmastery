<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 8:18 PM
 */

namespace CDCMastery\Models\CdcData;


use Monolog\Logger;

class AfscCollection
{
    const TABLE_NAME = 'afscList';

    const COL_UUID = 'uuid';
    const COL_NAME = 'name';
    const COL_DESCRIPTION = 'description';
    const COL_VERSION = 'version';
    const COL_IS_FOUO = 'fouo';
    const COL_IS_HIDDEN = 'hidden';

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    const DEFAULT_COL = self::COL_NAME;
    const DEFAULT_ORDER = self::ORDER_ASC;

    const SHOW_HIDDEN = 1 << 0;
    const SHOW_FOUO = 1 << 1;
    
    /**
     * @var \mysqli
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
     * @param \mysqli $mysqli
     * @param Logger $logger
     */
    public function __construct(\mysqli $mysqli, Logger $logger)
    {
        $this->db = $mysqli;
        $this->log = $logger;
    }

    /**
     * @param array $columnOrders
     * @return string
     */
    private static function generateOrderSuffix(array $columnOrders): string
    {
        if (empty($columnOrders)) {
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

        if ($showFouo && $showHidden) {
            goto out_query;
        }

        $qry = $omitWhere
            ? ' AND '
            : ' WHERE ';

        if (!$showFouo && !$showHidden) {
            $qry .= self::COL_IS_FOUO . ' = 0 AND ' . self::COL_IS_HIDDEN . ' = 0';
            goto out_query;
        }

        if (!$showFouo) {
            $qry .= self::COL_IS_FOUO . ' = 0';
            goto out_query;
        }

        if (!$showHidden) {
            $qry .= self::COL_IS_HIDDEN . ' = 0';
        }

        out_query:
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
  hidden
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
            $hidden
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

        $this->afscs[$uuid] = $afsc;

        return $afsc;
    }

    /**
     * @param array $columnOrders
     * @param int $flags
     * @return Afsc[]
     */
    public function fetchAll(array $columnOrders = [], int $flags = self::SHOW_FOUO): array
    {
        $qry = <<<SQL
SELECT
 uuid,
 name,
 description,
 version,
 fouo,
 hidden
FROM afscList
SQL;

        $qry .= self::generateWhereSuffix($flags);
        $qry .= self::generateOrderSuffix($columnOrders);

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $afsc = new Afsc();
            $afsc->setUuid($row['uuid'] ?? '');
            $afsc->setName($row['name'] ?? '');
            $afsc->setDescription($row['description'] ?? '');
            $afsc->setVersion($row['version'] ?? '');
            $afsc->setFouo((bool)($row['fouo'] ?? false));
            $afsc->setHidden((bool)($row['hidden'] ?? false));

            $this->afscs[$row['uuid']] = $afsc;
        }

        $res->free();

        return $this->afscs;
    }

    /**
     * @param array $uuidList
     * @param array $columnOrders
     * @param int $flags
     * @return Afsc[]
     */
    public function fetchArray(array $uuidList, array $columnOrders = [], int $flags = self::SHOW_FOUO): array
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
 hidden
FROM afscList
WHERE uuid IN ('{$uuidListString}')
SQL;

        $qry .= self::generateWhereSuffix($flags, true);
        $qry .= self::generateOrderSuffix($columnOrders);

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $afsc = new Afsc();
            $afsc->setUuid($row['uuid'] ?? '');
            $afsc->setName($row['name'] ?? '');
            $afsc->setDescription($row['description'] ?? '');
            $afsc->setVersion($row['version'] ?? '');
            $afsc->setFouo((bool)$row['fouo'] ?? false);
            $afsc->setHidden((bool)$row['hidden'] ?? false);

            $this->afscs[$row['uuid']] = $afsc;
        }

        $res->free();

        return array_intersect_key(
            $this->afscs,
            array_flip($uuidList)
        );
    }

    /**
     * @return AfscCollection
     */
    public function refresh(): self
    {
        $this->afscs = [];

        return $this;
    }

    /**
     * @param Afsc $afsc
     */
    public function save(Afsc $afsc): void
    {
        if (empty($afsc->getUuid())) {
            return;
        }

        $uuid = $afsc->getUuid();
        $name = $afsc->getName();
        $description = $afsc->getDescription();
        $version = $afsc->getVersion();
        $fouo = $afsc->isFouo();
        $hidden = $afsc->isHidden();

        $qry = <<<SQL
INSERT INTO afscList
  (
    uuid, 
    name, 
    description, 
    version, 
    fouo, 
    hidden
  ) 
VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  name=VALUES(name),
  description=VALUES(description),
  version=VALUES(version),
  fouo=VALUES(fouo),
  hidden=VALUES(hidden)
SQL;

        $stmt = $this->db->prepare($qry);
        $stmt->bind_param(
            'ssssii',
            $uuid,
            $name,
            $description,
            $version,
            $fouo,
            $hidden
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return;
        }

        $stmt->close();
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