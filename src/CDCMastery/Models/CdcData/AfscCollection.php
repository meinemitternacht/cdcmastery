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
  afscName,
  afscDescription,
  afscVersion,
  afscFOUO,
  afscHidden
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
     * @return Afsc[]
     */
    public function fetchAll(): array
    {
        $qry = <<<SQL
SELECT
 uuid,
 afscName,
 afscDescription,
 afscVersion,
 afscFOUO,
 afscHidden
FROM afscList
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $afsc = new Afsc();
            $afsc->setUuid($row['uuid'] ?? '');
            $afsc->setName($row['afscName'] ?? '');
            $afsc->setDescription($row['afscDescription'] ?? '');
            $afsc->setVersion($row['afscVersion'] ?? '');
            $afsc->setFouo((bool)$row['afscFOUO'] ?? false);
            $afsc->setHidden((bool)$row['afscHidden'] ?? false);

            $this->afscs[$row['uuid']] = $afsc;
        }

        $res->free();

        return $this->afscs;
    }

    /**
     * @param string[] $uuidList
     * @return Afsc[]
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
 afscName,
 afscDescription,
 afscVersion,
 afscFOUO,
 afscHidden
FROM afscList
WHERE uuid IN ('{$uuidListString}')
ORDER BY uuid ASC
SQL;

        $res = $this->db->query($qry);

        while ($row = $res->fetch_assoc()) {
            if (!isset($row['uuid']) || is_null($row['uuid'])) {
                continue;
            }

            $afsc = new Afsc();
            $afsc->setUuid($row['uuid'] ?? '');
            $afsc->setName($row['afscName'] ?? '');
            $afsc->setDescription($row['afscDescription'] ?? '');
            $afsc->setVersion($row['afscVersion'] ?? '');
            $afsc->setFouo((bool)$row['afscFOUO'] ?? false);
            $afsc->setHidden((bool)$row['afscHidden'] ?? false);

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
    afscName, 
    afscDescription, 
    afscVersion, 
    afscFOUO, 
    afscHidden
  ) 
VALUES (?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE 
  uuid=VALUES(uuid),
  afscName=VALUES(afscName),
  afscDescription=VALUES(afscDescription),
  afscVersion=VALUES(afscVersion),
  afscFOUO=VALUES(afscFOUO),
  afscHidden=VALUES(afscHidden)
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