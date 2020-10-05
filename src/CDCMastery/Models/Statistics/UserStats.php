<?php
declare(strict_types=1);

namespace CDCMastery\Models\Statistics;


use CDCMastery\Helpers\DBLogHelper;
use CDCMastery\Models\Cache\CacheHandler;
use Monolog\Logger;
use mysqli;

class UserStats
{
    private const STAT_USERS_BY_BASE = 'users_by_base';
    private const STAT_USERS_BY_GROUP = 'users_by_group';

    protected mysqli $db;
    protected Logger $log;
    protected CacheHandler $cache;

    /**
     * Users constructor.
     * @param mysqli $mysqli
     * @param Logger $logger
     * @param CacheHandler $cacheHandler
     */
    public function __construct(mysqli $mysqli, Logger $logger, CacheHandler $cacheHandler)
    {
        $this->db = $mysqli;
        $this->log = $logger;
        $this->cache = $cacheHandler;
    }

    /**
     * @return array
     */
    public function countsByBase(): array
    {
        $cached = $this->cache->hashAndGet(
            self::STAT_USERS_BY_BASE
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  userData.userBase AS uBase,
  COUNT(*) AS uCount
FROM userData
LEFT JOIN baseList ON userData.userBase = baseList.uuid
GROUP BY baseList.baseName
ORDER BY baseList.baseName, uCount DESC
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $counts = [];
        while ($row = $res->fetch_assoc()) {
            $counts[ $row[ 'uBase' ] ] = (int)($row[ 'uCount' ] ?? 0);
        }

        $res->free();

        $this->cache->hashAndSet(
            $counts,
            self::STAT_USERS_BY_BASE,
            CacheHandler::TTL_LARGE
        );

        return $counts;
    }

    /**
     * @return array
     */
    public function countsByRole(): array
    {
        $cached = $this->cache->hashAndGet(
            self::STAT_USERS_BY_GROUP
        );

        if ($cached !== false) {
            return $cached;
        }

        $qry = <<<SQL
SELECT 
  userData.userRole AS uRole,
  COUNT(*) AS uCount
FROM userData
GROUP BY uRole
ORDER BY uCount DESC
SQL;

        $res = $this->db->query($qry);

        if ($res === false) {
            DBLogHelper::query_error($this->log, __METHOD__, $qry, $this->db);
            return [];
        }

        $counts = [];
        while ($row = $res->fetch_assoc()) {
            $counts[ $row[ 'uRole' ] ] = (int)($row[ 'uCount' ] ?? 0);
        }

        $res->free();

        $this->cache->hashAndSet(
            $counts,
            self::STAT_USERS_BY_GROUP,
            CacheHandler::TTL_LARGE
        );

        return $counts;
    }
}